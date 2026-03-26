<?php

arch('livewire components should not access filesystem directly')
    ->expect('Ranetrace\\Lemme\\Livewire')
    ->not->toUse(['Illuminate\\Support\\Facades\\File', 'Illuminate\\Filesystem\\Filesystem']);
