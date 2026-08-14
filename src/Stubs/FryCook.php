<?php declare(strict_types=1);

namespace PatrickMaynard\MockItAll\Stubs;

class FryCook 
{
    public function __construct()
    {
        //We will require a SecretaryOfState, a SecretaryOfDefense and a SecretaryOfCommerce.
        //Each of them will require a ChiefOfStaff. 
        //The SecretaryOfState will also require a PersonalChef and a Janitor. 
        //The PersonalChef will require a FryCook. 
    }
}
