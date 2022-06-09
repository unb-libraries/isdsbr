<?php

$x = 0;

while ($x <= 345) {
  $id_val = $x + 2175;
  $padded_x = str_pad($x, 4, '0', STR_PAD_LEFT);
  echo "item_{$padded_x} 123456789/{$id_val}\n";
  $x++;
}
