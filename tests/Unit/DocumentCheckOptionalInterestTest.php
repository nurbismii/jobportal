<?php

namespace Tests\Unit;

use App\Services\DocumentCheck\DocumentCheck;
use PHPUnit\Framework\TestCase;

class DocumentCheckOptionalInterestTest extends TestCase
{
    public function test_hobbies_and_talents_are_not_required_to_apply_for_a_job(): void
    {
        $fieldLabels = (new DocumentCheck())->getFieldLabels();

        $this->assertArrayNotHasKey('hobi', $fieldLabels);
        $this->assertArrayNotHasKey('bakat', $fieldLabels);
    }
}
