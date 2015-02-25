<?php

class GeoserveFactory {

  private $db;
  private $db_dsn;
  private $db_user;
  private $db_pass;


  /**
   * Construct a new GeoserveFactory.
   *
   * @param $db_dsn {String}
   *        PDO DSN for database.
   *        Example: 'pgsql:host=localhost;port=5432;dbname=geoserve'.
   * @param $db_user {String}
   *        database username.
   * @param $db_pass {String}
   *        database password.
   */
  public function __construct($db_dsn, $db_user, $db_pass) {
    $this->db = null;
    $this->db_dsn = $db_dsn;
    $this->db_user = $db_user;
    $this->db_pass = $db_pass;
  }


  /**
   * Create connection for database.
   * Called during first use of factory.
   */
  public function connect() {
    if ($this->db === null) {
      $this->db = new PDO($this->db_dsn, $this->db_user, $this->db_pass);
      $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }
    return $this->db;
  }

  /**
   * Close connection to database.
   */
  public function disconnect() {
    $this->db = null;
  }


  /**
   * Get nearby places.
   *
   * @param $query {PlacesQuery}
   *        query object.
   * @return array of places, with these additional columns:
   *         "azimuth" - direction from search point to place,
   *                     in degrees clockwise from geographic north.
   *         "distance" - distance in meters
   * @throws Exception
   *         if at least one of $query->limit or $query->maxradiuskm
   *         is not specified.
   */
  public function getPlaces($query) {
    if ($query->limit === null && $query->maxradiuskm === null) {
      throw new Exception('"limit" and/or "maxradiuskm" is required');
    }

    // connect to database
    $db = $this->connect();

    // computed values
    $azimuth = 'degrees(ST_Azimuth(' .
          'ST_SetSRID(ST_MakePoint(:longitude,:latitude), 4326)::geography' .
          ',shape))';
    $distance = 'ST_Distance(' .
          'ST_SetSRID(ST_MakePoint(:longitude,:latitude), 4326)::geography' .
          ',shape)';
    // bound parameters
    $params = array(
        ':latitude' => $query->latitude,
        ':longitude' => $query->longitude);

    // create sql
    $sql =  'SELECT *' .
        ',' . $azimuth . ' as azimuth' .
        ',' .$distance . ' as distance' .
        ' FROM geoname';
    // build where clause
    $where = array();
    if ($query->maxradiuskm !== null) {
      $where[] = $distance . ' <= :distance';
      $params[':distance'] = $query->maxradiuskm * 1000;
    }
    if ($query->minpopulation !== null) {
      $where[] = 'population >= :population';
      $params[':population'] = $query->minpopulation;
    }
    if (count($where) > 0) {
      $sql .= ' WHERE ' . implode(' AND ', $where);
    }
    // sort closest places first
    $sql .= ' ORDER BY ' . $distance;
    // limit number of results
    if ($query->limit !== null) {
      $sql .= ' LIMIT :limit';
      $params[':limit'] = $query->limit;
    }

    // execute query
    try {
      $query = $db->prepare($sql);
      if (!$query->execute($params)) {
        $errorInfo = $db->errorInfo();
        throw new Exception($errorInfo[0] . ' (' . $errorInfo[1] . ') ' . $errorInfo[2]);
      }
      return $query->fetchAll(PDO::FETCH_ASSOC);
    } finally {
      // close handle
      $query = null;
    }
  }

}
