<?php

/**
 * AMQP protocol decimal value.
 *
 * Values are represented as (n,e) pairs. The actual value
 * is n * 10^(-e).
 *
 * From 0.8 spec: Decimal values are
 * not intended to support floating point values, but rather
 * business values such as currency rates and amounts. The
 * 'decimals' octet is not signed.
*/
class AMQP_Decimal
{
    public function __construct($n, $e)
    {
        if($e < 0)
        {
            throw new Exception("Decimal exponent value must be unsigned!");
        }
        $this->n = $n;
        $this->e = $e;
    }

    public function asBCvalue()
    {
        return bcdiv($n, bcpow(10,$e));
    }
}
