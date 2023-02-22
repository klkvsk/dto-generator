<?php

namespace Klkvsk\DtoGenerator\Schema;

enum ExtraFieldsPolicy
{
    case IGNORE;
    case THROW;
    // TODO: alternative implementation for php<8 because WeakMap is unavailable
    case COLLECT;
}
