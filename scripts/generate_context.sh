#!/bin/bash

# Script para generar contexto automático del proyecto
# Ejecutar: ./scripts/generate_context.sh

echo "🔍 Generando contexto automático del proyecto..."
echo "Fecha: $(date)"
echo "============================================="

CONTEXT_FILE="docs/AUTO_GENERATED_CONTEXT.md"
mkdir -p docs

echo "# CONTEXTO AUTO-GENERADO - $(date)" > $CONTEXT_FILE
echo "" >> $CONTEXT_FILE

echo "## 📊 Estado de la Base de Datos" >> $CONTEXT_FILE
echo '```' >> $CONTEXT_FILE
php artisan tinker --execute="
echo 'Tabla imported_emails: ' . App\Models\ImportedEmail::count() . ' registros';
echo 'Tabla gmail_groups: ' . App\Models\GmailGroup::count() . ' registros';
echo '';
echo 'Estados de correos:';
foreach(App\Models\ImportedEmail::selectRaw('case_status, count(*) as total')->groupBy('case_status')->get() as \$status) {
    echo '- ' . \$status->case_status . ': ' . \$status->total;
}
echo '';
echo 'Usuario 16069813 (Lucas Muñoz):';
echo '- Correos asignados: ' . App\Models\ImportedEmail::where('assigned_to', 16069813)->count();
" >> $CONTEXT_FILE
echo '```' >> $CONTEXT_FILE

echo "" >> $CONTEXT_FILE
echo "## 🏗️ Estructura de Modelos" >> $CONTEXT_FILE

for model in app/Models/*.php; do
    if [ -f "$model" ]; then
        echo "### $(basename $model)" >> $CONTEXT_FILE
        echo '```php' >> $CONTEXT_FILE
        head -30 "$model" | grep -E "(class|protected|public function)" >> $CONTEXT_FILE
        echo '```' >> $CONTEXT_FILE
        echo "" >> $CONTEXT_FILE
    fi
done

echo "## 📝 Componentes Livewire Principales" >> $CONTEXT_FILE

for component in resources/views/livewire/inbox/*.blade.php; do
    if [ -f "$component" ]; then
        echo "### $(basename $component)" >> $CONTEXT_FILE
        echo '```php' >> $CONTEXT_FILE
        head -20 "$component" | grep -E "(public function|new class)" >> $CONTEXT_FILE
        echo '```' >> $CONTEXT_FILE
        echo "" >> $CONTEXT_FILE
    fi
done

echo "## 🔧 Comandos Artisan Disponibles" >> $CONTEXT_FILE
echo '```bash' >> $CONTEXT_FILE
php artisan list | grep -E "(gmail|email)" >> $CONTEXT_FILE
echo '```' >> $CONTEXT_FILE

echo "## 🌐 Estado de URLs" >> $CONTEXT_FILE
echo '```' >> $CONTEXT_FILE
echo "Verificando URLs principales..."
curl -I -s https://dev-estadisticas.orpro.cl/debug/agente | head -1 >> $CONTEXT_FILE
curl -I -s https://dev-estadisticas.orpro.cl/debug/supervisor | head -1 >> $CONTEXT_FILE
echo '```' >> $CONTEXT_FILE

echo "✅ Contexto generado en: $CONTEXT_FILE"
echo "📄 Archivos de contexto disponibles:"
echo "   - CONTEXT.md (Manual, completo)"
echo "   - TECHNICAL_CONTEXT.md (Técnico, desarrollo)"
echo "   - docs/AUTO_GENERATED_CONTEXT.md (Auto-generado)"