/*
 * bigfisher
 * https://github.com/sackio/bigfisher
 *
 * Copyright (c) 2014 Ben Sack
 * Licensed under the MIT license.
 */

var BigBinom = require('bigbinom');

module.exports = function(a, b, c, d){
  var bn1 = new BigBinom(a + b, a)
    , bn2 = new BigBinom(c + d, c)
    , bn3 = new BigBinom(a + b + c + d, a + c);

  return bn1.times(bn2).dividedBy(bn3);
};
