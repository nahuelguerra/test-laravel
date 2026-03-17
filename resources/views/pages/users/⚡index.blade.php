<?php

use App\Models\Central\Tenant;
use App\Models\User;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new
#[Layout('layouts.app')]
#[Title('Usuarios')]
class extends Component {
    #[Computed]
    public function users()
    {
        return User::with('roles')->orderBy('name')->get();
    }

    #[Computed]
    public function tenantName(): string
    {
        $tenant = Tenant::checkCurrent() ? Tenant::current() : null;

        return $tenant ? $tenant->name : 'Central (planes_central)';
    }
}; ?>

<div class="w-full">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <flux:heading size="xl">{{ __('Usuarios') }}</flux:heading>
            <flux:text class="mt-1">{{ $this->tenantName }}</flux:text>
        </div>
        <flux:badge color="zinc" size="lg">{{ $this->users->count() }} {{ __('usuarios') }}</flux:badge>
    </div>

    <flux:table>
        <flux:table.columns>
            <flux:table.column>{{ __('Nombre') }}</flux:table.column>
            <flux:table.column>{{ __('Email') }}</flux:table.column>
            <flux:table.column>{{ __('Rol') }}</flux:table.column>
            <flux:table.column>{{ __('Estado') }}</flux:table.column>
            <flux:table.column>{{ __('Creado') }}</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @foreach ($this->users as $user)
                <flux:table.row :key="$user->id">
                    <flux:table.cell class="flex items-center gap-3">
                        <flux:avatar size="sm" :name="$user->name" :initials="$user->initials()" />
                        {{ $user->name }}
                    </flux:table.cell>

                    <flux:table.cell>
                        {{ $user->email }}
                    </flux:table.cell>

                    <flux:table.cell>
                        @foreach ($user->roles as $role)
                            <flux:badge size="sm" variant="pill" color="blue">
                                {{ \App\Enums\RoleEnum::from($role->name)->label() }}
                            </flux:badge>
                        @endforeach
                    </flux:table.cell>

                    <flux:table.cell>
                        @if ($user->is_active)
                            <flux:badge size="sm" color="green">Activo</flux:badge>
                        @else
                            <flux:badge size="sm" color="red">Inactivo</flux:badge>
                        @endif
                    </flux:table.cell>

                    <flux:table.cell class="text-zinc-500">
                        {{ $user->created_at->format('d/m/Y') }}
                    </flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>
</div>
