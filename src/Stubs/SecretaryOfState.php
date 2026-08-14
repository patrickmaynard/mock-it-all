<?php declare(strict_types=1);

namespace PatrickMaynard\MockItAll\Stubs;

use PatrickMaynard\MockItAll\Stubs\ChiefOfStaff;
use PatrickMaynard\MockItAll\Stubs\Janitor;
use PatrickMaynard\MockItAll\Stubs\PersonalChef;

class SecretaryOfState 
{
    public function __construct(ChiefOfStaff $chiefOfStaff, Janitor $janitor, PersonalChef $personalChef)
    {
        //We will require a SecretaryOfState, a SecretaryOfDefense and a SecretaryOfCommerce.
        //Each of them will require a ChiefOfStaff. 
        //The SecretaryOfState will also require a PersonalChef and a Janitor. 
        //The PersonalChef will require a FryCook. 
    }
}
