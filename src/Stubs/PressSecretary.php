<?php declare(strict_types=1);

namespace PatrickMaynard\MockItAll\Stubs;

use PatrickMaynard\MockItAll\Stubs\ChiefOfStaff;

class PressSecretary
{
    public function __construct(string $briefingRoom, ChiefOfStaff $chiefOfStaff)
    {
        //Used to exercise the scalar/builtin constructor-parameter branch of
        //MockLogicCreator: $briefingRoom is a plain string, not a class we
        //can mock or recurse into, so it should be inlined as a placeholder
        //literal instead of a generated mock property.
    }
}
