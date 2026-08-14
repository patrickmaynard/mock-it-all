<?php declare(strict_types=1);

namespace PatrickMaynard\MockItAll\Stubs;

use PatrickMaynard\MockItAll\Stubs\FryCook;

class PersonalChef 
{
    public function __construct(FryCook $fryCook)
    {
        //We will require a SecretaryOfState, a SecretaryOfDefense and a SecretaryOfCommerce.
        //Each of them will require a ChiefOfStaff. 
        //The SecretaryOfState will also require a PersonalChef and a Janitor. 
        //The PersonalChef will require a FryCook. 
    }
}
