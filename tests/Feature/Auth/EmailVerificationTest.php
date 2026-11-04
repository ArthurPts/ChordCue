<?php

// Email verification is out-of-scope for ChordCue as specified in SPESIFIKASI_SISTEM_ChordCue.md §5.1
test('email verification is disabled in ChordCue', function () {
    $this->markTestSkipped('Email verification is intentionally out-of-scope in ChordCue specifications.');
});
