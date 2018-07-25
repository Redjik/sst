<?php

/**
 * @param string|int $a
 * @param string|int $b
 * @return string
 * @throws \InvalidArgumentException
 */
function sum($a, $b) : string
{
    validateInts($a, $b);

    $a = strrev($a);
    $b = strrev($b);

    $result = "";
    $ten = 0;

    for( $i = 0; $i < max(strlen($a), strlen($b)); $i++ ) {


        $aPiece = $a[$i] ?? null;
        $bPiece = $b[$i] ?? null;

        $resultPiece = (int)$aPiece + (int)$bPiece + $ten;

        if ($resultPiece > 9) {
            $resultPiece%=10;
            $ten = 1;
        }else{
            $ten = 0;
        }

        $result[$i] = $resultPiece;
    }

    if ($ten > 0) {
        $result .= $ten;
    }

    return strrev($result);
}

/**
 * @param string|int ...$ints
 * @throws \InvalidArgumentException
 */
function validateInts(...$ints)
{
    foreach ($ints as $int) {
        if ((string)$int === '0') {
            continue;
        }

        //we want only numbers
        if (!is_int($int) && !ctype_digit($int)) {
            throw new \InvalidArgumentException($int . ' is not an int.');
        }

        //we don't want octal
        if ((string)$int[0] === '0') {
            throw new \InvalidArgumentException($int . ' is not an int (but possibly octal).');
        }
    }
}

var_dump(sum(600, "123"));