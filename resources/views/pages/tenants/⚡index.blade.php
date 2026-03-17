<?php

use App\Models\Central\Tenant;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;

new
#[Layout('layouts.app')]
#[Title('Concesionarias')]
class extends Component {
    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('required|string|max:255|unique:landlord.tenants,domain')]
    public string $domain = '';

    #[Validate('required|string|max:255')]
    public string $adminName = 'Administrador';

    #[Validate('required|email|max:255')]
    public string $adminEmail = '';

    public bool $showCreateModal = false;

    #[Computed]
    public function tenants()
    {
        return Tenant::orderBy('name')->get();
    }

    public function updatedName(string $value): void
    {
        $slug = Str::slug($value);
        $this->domain = $slug . '.localhost';

        if (empty($this->adminEmail) || Str::endsWith($this->adminEmail, '.com')) {
            $this->adminEmail = 'admin@' . $slug . '.com';
        }
    }

    public function create(): void
    {
        $this->validate();

        $database = 'tenant_' . Str::slug($this->name, '_');

        if (Tenant::where('database', $database)->exists()) {
            $database .= '_' . time();
        }

        $tenant = new Tenant([
            'name' => $this->name,
            'domain' => $this->domain,
            'database' => $database,
        ]);

        // Datos temporales para que TenantObserver cree el usuario admin
        $tenant->admin_name = $this->adminName;
        $tenant->admin_email = $this->adminEmail;
        $tenant->save();

        $this->reset('name', 'domain', 'adminName', 'adminEmail', 'showCreateModal');
        $this->adminName = 'Administrador';
        unset($this->tenants);

        session()->flash('success', "Concesionaria creada. Usuario admin: {$tenant->admin_email} / password");
    }
}; ?>

<div class="w-full">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <flux:heading size="xl">{{ __('Concesionarias') }}</flux:heading>
            <flux:text class="mt-1">Gestión de empresas (tenants)</flux:text>
        </div>
        <flux:button variant="primary" icon="plus" wire:click="$set('showCreateModal', true)">
            Nueva Concesionaria
        </flux:button>
    </div>

    @if (session('success'))
        <flux:callout variant="success" class="mb-4" icon="check-circle">
            {{ session('success') }}
        </flux:callout>
    @endif

    <flux:table>
        <flux:table.columns>
            <flux:table.column>{{ __('ID') }}</flux:table.column>
            <flux:table.column>{{ __('Nombre') }}</flux:table.column>
            <flux:table.column>{{ __('Dominio') }}</flux:table.column>
            <flux:table.column>{{ __('Base de datos') }}</flux:table.column>
            <flux:table.column>{{ __('Estado') }}</flux:table.column>
            <flux:table.column>{{ __('Creado') }}</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($this->tenants as $tenant)
                <flux:table.row :key="$tenant->id">
                    <flux:table.cell>{{ $tenant->id }}</flux:table.cell>

                    <flux:table.cell class="font-medium">
                        {{ $tenant->name }}
                    </flux:table.cell>

                    <flux:table.cell>
                        <flux:link href="http://{{ $tenant->domain }}:8000" target="_blank">
                            {{ $tenant->domain }}
                        </flux:link>
                    </flux:table.cell>

                    <flux:table.cell class="font-mono text-sm">
                        {{ $tenant->database }}
                    </flux:table.cell>

                    <flux:table.cell>
                        @if ($tenant->is_active)
                            <flux:badge size="sm" color="green">Activo</flux:badge>
                        @else
                            <flux:badge size="sm" color="red">Inactivo</flux:badge>
                        @endif
                    </flux:table.cell>

                    <flux:table.cell class="text-zinc-500">
                        {{ $tenant->created_at->format('d/m/Y H:i') }}
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="6" class="text-center text-zinc-500">
                        No hay concesionarias registradas.
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    {{-- Modal de creación --}}
    <flux:modal wire:model="showCreateModal" name="create-tenant">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Nueva Concesionaria</flux:heading>
                <flux:text class="mt-1">Se creará automáticamente la base de datos, tablas, roles y permisos.</flux:text>
            </div>

            <form wire:submit="create" class="space-y-4">
                <flux:input
                    wire:model.live.debounce.300ms="name"
                    :label="__('Nombre de la empresa')"
                    placeholder="Ej: Automotores del Sur"
                    required
                />

                <flux:input
                    wire:model="domain"
                    :label="__('Dominio')"
                    placeholder="Ej: automotores-del-sur.localhost"
                    description="Subdominio que usará la concesionaria para acceder al sistema."
                    required
                />

                <flux:separator text="Usuario administrador" />

                <flux:input
                    wire:model="adminName"
                    :label="__('Nombre del admin')"
                    placeholder="Ej: Juan Pérez"
                    required
                />

                <flux:input
                    wire:model="adminEmail"
                    type="email"
                    :label="__('Email del admin')"
                    placeholder="Ej: admin@empresa.com"
                    description="Se creará con contraseña: password"
                    required
                />

                <div class="flex justify-end gap-3 pt-2">
                    <flux:button variant="ghost" wire:click="$set('showCreateModal', false)">
                        Cancelar
                    </flux:button>
                    <flux:button variant="primary" type="submit" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="create">Crear Concesionaria</span>
                        <span wire:loading wire:target="create">Provisionando...</span>
                    </flux:button>
                </div>
            </form>
        </div>
    </flux:modal>
</div>
