<x-layout title="Confirmar Restauração de Backup">
    <div class="mx-auto flex max-w-3xl flex-col gap-6 px-5 py-8 sm:px-8 sm:py-10">
        <x-page-header
            title="Confirmar Restauração"
            description="Atenção: esta ação substituirá os dados atuais do banco de dados pelo conteúdo do arquivo de backup."
            :back-href="route('backup.settings')"
            back-label="Voltar para Central de Backup"
        />

        <x-flash />

        <x-alert type="danger" title="Ação Crítica e Irreversível">
            A restauração substituirá todas as tabelas e registros existentes do sistema.
            Antes de aplicar o arquivo, o sistema criará automaticamente um <strong>backup de segurança de emergência</strong>
            (com prefixo <code>pre-restauracao_</code>) para permitir recuperação caso necessário.
        </x-alert>

        <x-card size="lg">
            <div class="flex flex-col gap-6">
                <div>
                    <h2 class="text-base font-semibold text-ink">Arquivo Selecionado para Restauração</h2>
                    <dl class="mt-3 divide-y divide-line rounded-lg border border-line bg-surface-2 text-sm">
                        <div class="flex justify-between px-4 py-2.5">
                            <dt class="font-medium text-ink-soft">Nome do Arquivo:</dt>
                            <dd class="font-mono text-xs font-semibold text-ink">{{ $file }}</dd>
                        </div>
                        <div class="flex justify-between px-4 py-2.5">
                            <dt class="font-medium text-ink-soft">Data do Backup:</dt>
                            <dd class="text-ink">{{ $dateFormatted }}</dd>
                        </div>
                        <div class="flex justify-between px-4 py-2.5">
                            <dt class="font-medium text-ink-soft">Tamanho:</dt>
                            <dd class="font-mono text-xs text-ink">{{ $sizeFormatted }}</dd>
                        </div>
                    </dl>
                </div>

                <form action="{{ route('backup.restore', ['file' => $file]) }}" method="POST" class="flex flex-col gap-5">
                    @csrf

                    <div>
                        <label for="confirm_text" class="block text-sm font-medium text-ink">
                            Para confirmar, digite <span class="font-mono font-bold text-red6">RESTAURAR</span> no campo abaixo:
                        </label>
                        <input
                            type="text"
                            id="confirm_text"
                            name="confirm_text"
                            autocomplete="off"
                            required
                            placeholder="Digite RESTAURAR"
                            class="mt-1 block w-full rounded-md border border-line bg-surface-1 px-3 py-2 text-sm font-mono text-ink shadow-sm uppercase placeholder:text-ink-muted focus:border-red6 focus:outline-none focus:ring-1 focus:ring-red6"
                        />
                        @error('confirm_text')
                            <p class="mt-1 text-xs text-red6">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex items-center justify-end gap-3 border-t border-line pt-4">
                        <x-button variant="secondary" :href="route('backup.settings')">
                            Cancelar
                        </x-button>

                        <button
                            type="submit"
                            class="inline-flex items-center justify-center rounded-md bg-red6 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-red7 focus:outline-none focus:ring-2 focus:ring-red6 focus:ring-offset-2"
                        >
                            Confirmar e Restaurar Banco
                        </button>
                    </div>
                </form>
            </div>
        </x-card>
    </div>
</x-layout>
