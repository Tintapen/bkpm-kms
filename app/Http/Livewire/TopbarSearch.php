<?php

namespace App\Livewire;

use Livewire\Component;

class TopbarSearch extends Component
{
    public $search = '';

    public function updatedSearch($value)
    {
        $this->emitTo('article-list', 'globalSearchUpdated', $value);
    }

    public function render()
    {
        return view('livewire.topbar-search');
    }
}
