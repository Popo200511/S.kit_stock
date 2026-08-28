<?php

namespace App\Livewire;

use App\Support\Nav;
use Livewire\Component;

class ComingSoon extends Component
{
    public string $pageTitle;

    public string $pageSubtitle;

    public function mount(): void
    {
        $page = collect(Nav::pages())->firstWhere('route', request()->route()->getName());

        $this->pageTitle = $page['label'] ?? '';
        $this->pageSubtitle = $page['sub'] ?? '';
    }

    public function render()
    {
        return view('livewire.coming-soon')
            ->layout('components.layouts.app', ['title' => $this->pageTitle, 'subtitle' => $this->pageSubtitle]);
    }
}
