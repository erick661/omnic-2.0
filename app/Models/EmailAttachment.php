<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class EmailAttachment extends Model
{
    protected $fillable = [
        'imported_email_id',
        'original_filename',
        'stored_filename',
        'file_path',
        'mime_type',
        'file_size',
    ];

    /**
     * Correo al que pertenece este adjunto
     */
    public function importedEmail(): BelongsTo
    {
        return $this->belongsTo(ImportedEmail::class);
    }

    /**
     * Obtiene el tamaño del archivo formateado
     */
    public function getFormattedSizeAttribute(): string
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Obtiene la URL del archivo
     */
    public function getUrlAttribute(): string
    {
        return Storage::url($this->file_path);
    }

    /**
     * Verifica si el archivo existe en storage
     */
    public function exists(): bool
    {
        return Storage::exists($this->file_path);
    }

    /**
     * Elimina el archivo del storage
     */
    public function deleteFile(): bool
    {
        if ($this->exists()) {
            return Storage::delete($this->file_path);
        }
        
        return true;
    }

    /**
     * Obtiene el contenido del archivo
     */
    public function getContent(): ?string
    {
        if ($this->exists()) {
            return Storage::get($this->file_path);
        }
        
        return null;
    }

    /**
     * Verifica si es una imagen
     */
    public function isImage(): bool
    {
        return str_starts_with($this->mime_type, 'image/');
    }

    /**
     * Verifica si es un PDF
     */
    public function isPdf(): bool
    {
        return $this->mime_type === 'application/pdf';
    }

    /**
     * Verifica si es un documento de texto
     */
    public function isDocument(): bool
    {
        $documentTypes = [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'text/plain',
        ];
        
        return in_array($this->mime_type, $documentTypes);
    }
}
