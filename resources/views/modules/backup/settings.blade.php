<x-layout title="Central de Backup">
    <div class="mx-auto flex max-w-7xl flex-col gap-6 px-5 py-8 sm:px-8 sm:py-10">
        <x-page-header
            title="Central de Backup"
            description="Gerencie as cópias de segurança do banco de dados, destinos adicionais e restaurações."
            :back-href="route('site.index')"
            back-label="Voltar ao início"
        >
            <x-slot:actions>
                <form action="{{ route('backup.run') }}" method="POST" onsubmit="return confirm('Deseja gerar um novo backup do banco de dados agora?')">
                    @csrf
                    <x-button variant="primary" type="submit">
                        <span class="inline-flex items-center gap-2">
                            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                            </svg>
                            GERAR BACKUP AGORA
                        </span>
                    </x-button>
                </form>
            </x-slot:actions>
        </x-page-header>

        <x-flash />

        @if (session('test_success'))
            <x-alert type="success">
                {{ session('test_success') }}
            </x-alert>
        @endif

        @if (session('test_error'))
            <x-alert type="danger">
                {{ session('test_error') }}
            </x-alert>
        @endif

        {{-- Estatísticas rápidas --}}
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <x-stat
                label="Total de backups locais"
                :value="count($backups)"
                tone="blue"
            />
            <x-stat
                label="Espaço ocupado em disco"
                :value="$totalFormatted"
                tone="neutral"
            />
            <x-stat
                label="Último backup realizado"
                :value="count($backups) > 0 ? $backups[0]['created_at_formatted'] : 'Nenhum'"
                :tone="count($backups) > 0 ? 'green' : 'amber'"
            />
        </div>

        @if ($lastRestore !== null)
            <x-card size="md">
                <div class="flex flex-col gap-2">
                    <div class="flex items-center justify-between">
                        <h2 class="text-base font-semibold">Última Restauração Realizada</h2>
                        @if (($lastRestore['status'] ?? '') === 'success')
                            <span class="inline-flex items-center gap-1 rounded-full border border-grn2 bg-grn1 px-2.5 py-0.5 text-xs font-medium text-grn7">
                                Sucesso
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 rounded-full border border-red2 bg-red1 px-2.5 py-0.5 text-xs font-medium text-red7">
                                Falha
                            </span>
                        @endif
                    </div>
                    <p class="text-sm text-ink-soft">
                        Data: <span class="font-medium text-ink">{{ $lastRestore['restored_at'] ?? 'N/D' }}</span> |
                        Arquivo: <span class="font-medium text-ink">{{ $lastRestore['filename'] ?? 'N/D' }}</span>
                        @if (! empty($lastRestore['pre_restore_backup']))
                            | Backup de segurança: <span class="font-medium text-ink">{{ $lastRestore['pre_restore_backup'] }}</span>
                        @endif
                    </p>
                    @if (! empty($lastRestore['message']))
                        <p class="text-xs text-ink-muted">{{ $lastRestore['message'] }}</p>
                    @endif
                </div>
            </x-card>
        @endif

        {{-- Configuração de Destino Secundário --}}
        <x-card size="lg">
            <div class="flex flex-col gap-5">
                <div class="border-b border-line pb-4">
                    <h2 class="text-lg font-semibold">Destino Secundário (Pendrive, HD Externo ou Rede)</h2>
                    <p class="mt-1 text-sm text-ink-soft">
                        Ao gerar um backup, o sistema salvará uma cópia adicional automaticamente nesta pasta caso configurada.
                    </p>
                </div>

                <form action="{{ route('backup.extra.update') }}" method="POST" class="flex flex-col gap-4">
                    @csrf
                    @method('PUT')

                    <div>
                        <label for="extra_destination_path" class="block text-sm font-medium text-ink">
                            Caminho da pasta de destino extra
                        </label>
                        <div class="mt-1 flex flex-col gap-3 sm:flex-row">
                            <input
                                type="text"
                                id="extra_destination_path"
                                name="extra_destination_path"
                                value="{{ old('extra_destination_path', $settings['extra_destination_path'] ?? '') }}"
                                placeholder="Ex: E:\Backups ou \\servidor\compartilhamento\backups"
                                class="block w-full rounded-md border border-line bg-surface-1 px-3 py-2 text-sm text-ink shadow-sm placeholder:text-ink-muted focus:border-blu6 focus:outline-none focus:ring-1 focus:ring-blu6"
                            />
                            <div class="flex shrink-0 items-center gap-2">
                                <x-button variant="primary" type="submit">Salvar caminho</x-button>
                            </div>
                        </div>
                    </div>
                </form>

                {{-- Ações e diagnósticos do destino secundário --}}
                @if (! empty($settings['extra_destination_path']))
                    <div class="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-line bg-surface-2 p-3 text-sm">
                        <div class="flex flex-col gap-1">
                            <div>
                                <span class="font-medium text-ink">Último teste:</span>
                                @if ($settings['last_test_status'] === 'ok')
                                    <span class="inline-flex items-center gap-1 rounded px-1.5 py-0.5 text-xs font-medium text-grn7 bg-grn1 border border-grn2">Acessível</span>
                                @elseif ($settings['last_test_status'] === 'error')
                                    <span class="inline-flex items-center gap-1 rounded px-1.5 py-0.5 text-xs font-medium text-red7 bg-red1 border border-red2">Falha</span>
                                @else
                                    <span class="text-ink-muted">Ainda não testado</span>
                                @endif
                                <span class="ml-1 text-xs text-ink-soft">({{ $settings['last_test_at'] ?? 'Nunca' }})</span>
                            </div>
                            @if (! empty($settings['last_test_message']))
                                <p class="text-xs text-ink-soft">{{ $settings['last_test_message'] }}</p>
                            @endif

                            @if (! empty($settings['last_copy_at']))
                                <div class="mt-1 border-t border-line/60 pt-1 text-xs">
                                    <span class="font-medium text-ink">Última cópia automática:</span>
                                    @if ($settings['last_copy_status'] === 'ok')
                                        <span class="text-grn7 font-medium">Sucesso</span>
                                    @else
                                        <span class="text-red7 font-medium">Falha</span>
                                    @endif
                                    <span class="text-ink-soft">({{ $settings['last_copy_at'] }}) - {{ $settings['last_copy_message'] }}</span>
                                </div>
                            @endif
                        </div>

                        <div class="flex items-center gap-2">
                            <form action="{{ route('backup.extra.test') }}" method="POST">
                                @csrf
                                <input type="hidden" name="extra_destination_path" value="{{ $settings['extra_destination_path'] }}" />
                                <x-button variant="secondary" type="submit" size="sm">
                                    Testar destino
                                </x-button>
                            </form>

                            <form action="{{ route('backup.extra.remove') }}" method="POST" onsubmit="return confirm('Deseja remover o destino extra configurado?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-xs font-medium text-red6 hover:text-red7 hover:underline">
                                    Remover
                                </button>
                            </form>
                        </div>
                    </div>
                @endif
            </div>
        </x-card>

        {{-- Lista de backups existentes --}}
        <x-card size="lg">
            <div class="flex flex-col gap-4">
                <div class="flex items-center justify-between border-b border-line pb-3">
                    <div>
                        <h2 class="text-lg font-semibold">Backups Locais Armazenados</h2>
                        <p class="text-sm text-ink-soft">
                            Pasta: <code class="rounded bg-surface-2 px-1.5 py-0.5 text-xs">{{ config('backup.path') }}</code>
                        </p>
                    </div>
                    <span class="text-xs text-ink-muted">Retenção padrão: 30 backups mais recentes</span>
                </div>

                @if (empty($backups))
                    <div class="py-8 text-center text-sm text-ink-muted">
                        Nenhum backup encontrado na pasta local.
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead class="border-b border-line text-xs font-semibold text-ink-soft">
                                <tr>
                                    <th class="py-2.5 pr-4">Nome do arquivo</th>
                                    <th class="px-4 py-2.5">Data e Hora</th>
                                    <th class="px-4 py-2.5">Tamanho</th>
                                    <th class="px-4 py-2.5">Integridade</th>
                                    @if ($restoreEnabled)
                                        <th class="py-2.5 pl-4 text-right">Ações</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-line">
                                @foreach ($backups as $b)
                                    <tr class="hover:bg-surface-2/50">
                                        <td class="py-3 pr-4 font-mono text-xs text-ink">
                                            {{ $b['filename'] }}
                                            @if ($b['is_pre_restore'])
                                                <span class="ml-1.5 inline-flex rounded bg-surface-3 px-1.5 py-0.5 text-[10px] font-sans font-medium text-ink-muted">
                                                    Pré-restauração
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-ink-soft whitespace-nowrap">
                                            {{ $b['created_at_formatted'] }}
                                        </td>
                                        <td class="px-4 py-3 font-mono text-xs text-ink-soft whitespace-nowrap">
                                            {{ $b['size_formatted'] }}
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            @if ($b['is_valid'])
                                                <span class="inline-flex items-center gap-1 text-xs font-medium text-grn7">
                                                    <span class="size-1.5 rounded-full bg-grn6"></span>
                                                    Completo
                                                </span>
                                            @else
                                                <span class="inline-flex items-center gap-1 text-xs font-medium text-red7">
                                                    <span class="size-1.5 rounded-full bg-red6"></span>
                                                    Inválido
                                                </span>
                                            @endif
                                        </td>
                                        @if ($restoreEnabled)
                                            <td class="py-3 pl-4 text-right whitespace-nowrap">
                                                <a
                                                    href="{{ route('backup.restore.confirm', ['file' => $b['filename']]) }}"
                                                    class="inline-flex items-center rounded border border-line bg-surface-1 px-2.5 py-1 text-xs font-medium text-ink shadow-sm hover:bg-surface-2 hover:text-red6"
                                                >
                                                    Restaurar
                                                </a>
                                            </td>
                                        @endif
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </x-card>
    </div>
</x-layout>
