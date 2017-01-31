<?php

function getConfigFn() {
  // config preprocess
  $configFn = [
    'base'=>__DIR__.'/../etc/config.yml',
    'override'=>__DIR__.'/../etc/config.override.yml'
  ];

  if(file_exists($configFn['override'])) {
    return $configFn['override'];
  }

  if(file_exists($configFn['base'])) {
    return $configFn['base'];
  }

  throw new \Exception("etc/config.yml not found (nor config.override.yml)");
}
