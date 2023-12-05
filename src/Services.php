<?php

namespace Inertia;

class Services
{
    public static function inertia($getShared = true)
    {
        return new Factory;
    }
}
