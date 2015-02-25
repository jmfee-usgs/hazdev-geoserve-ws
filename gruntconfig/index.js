'use strict';

var gruntConfig = {
  config: require('./config'),
  
  clean: require('./clean'),
  compass: require('./compass'),
  concurrent: require('./concurrent'),
  connect: require('./connect'),
  copy: require('./copy'),
  cssmin: require('./cssmin'),
  jshint: require('./jshint'),
  uglify: require('./uglify'),
  watch: require('./watch'),

  tasks: [
    'grunt-concurrent',
    'grunt-connect-proxy',
    'grunt-connect-rewrite',
    'grunt-contrib-clean',
    'grunt-contrib-compass',
    'grunt-contrib-connect',
    'grunt-contrib-copy',
    'grunt-contrib-cssmin',
    'grunt-contrib-jshint',
    'grunt-contrib-uglify',
    'grunt-contrib-watch'
  ]
};

module.exports = gruntConfig;
