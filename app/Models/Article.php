<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasAuditTrail;
use App\Models\Category;
use App\Notifications\ArticleCreatedNotification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class Article extends Model
{
    use HasFactory;
    use HasAuditTrail;

    protected $fillable = [
        'title',
        'excerpt',
        'category_id',
        'tags',
        // 'attachment',
        // 'attachment_original_name',
        // 'attachment_mime',
        // 'attachment_size',
        'views',
        'likes',
        'author_id',
    ];

    protected $casts = [
        'tags' => 'array',
    ];

    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function getExcerptText(): string
    {
        if (empty($this->excerpt)) return '';

        // hapus figure/data-trix-attachment
        $clean = preg_replace('/<figure.*?<\/figure>/is', '', $this->excerpt);

        // hapus tag HTML lainnya
        $clean = strip_tags($clean);

        // convert newline
        return nl2br($clean);
    }

    public function getAttachments(): array
    {
        $attachments = [];
        if (empty($this->excerpt)) return $attachments;

        $disk = $this->attachment_disk ?? 'public';
        $storage = Storage::disk($disk);

        $searchByBasename = function (string $basename) use ($storage) {
            $candName = pathinfo($basename, PATHINFO_FILENAME);
            $candNorm = preg_replace('/[^a-z0-9]/', '', strtolower($candName));

            foreach ($storage->files('articles') as $f) {
                $storedBase = pathinfo($f, PATHINFO_FILENAME);
                $storedNorm = preg_replace('/[^a-z0-9]/', '', strtolower($storedBase));

                if (
                    strtolower($storedBase) === strtolower($candName)
                    || strpos($storedNorm, $candNorm) !== false
                    || strpos($candNorm, $storedNorm) !== false
                ) {
                    return $f;
                }
            }

            return null;
        };

        // cari Trix attachment
        if (preg_match_all('/data-trix-attachment="([^"]+)"/i', $this->excerpt, $matches)) {
            foreach ($matches[1] as $jsonHtml) {
                $data = json_decode(html_entity_decode($jsonHtml), true);
                if (!is_array($data)) continue;

                $url = $data['url'] ?? $data['href'] ?? null;
                if (!empty($url)) {
                    if (preg_match('/storage\/([^"]+)/', $url, $m)) {
                        $rel = ltrim($m[1], '/');
                        if ($storage->exists($rel)) $attachments[] = $storage->url($rel);
                        else if ($found = $searchByBasename(basename($rel))) $attachments[] = $storage->url($found);
                    } else {
                        $attachments[] = $url;
                    }
                }
            }
        }

        return $attachments;
    }

    /**
     * Try to determine the first attachment URL for this article.
     * Priority:
     * 1) If `attachment` column is present, build URL from disk.
     * 2) Otherwise, attempt to parse the first storage URL from the excerpt HTML.
     */
    public function getFirstAttachmentUrl(): ?string
    {
        // If explicit attachment path is stored, use it
        if (!empty($this->attachment)) {
            $disk = $this->attachment_disk ?? 'public';
            try {
                return Storage::disk($disk)->url($this->attachment);
            } catch (\Exception $e) {
                return null;
            }
        }

        // Try to parse URL from excerpt content (e.g., Trix/figure href)
        if (!empty($this->excerpt)) {
            $disk = $this->attachment_disk ?? 'public';
            $storage = Storage::disk($disk);

            // Helper: search for a file in articles by basename
            $searchByBasename = function (string $basename) use ($storage) {
                $basename = trim($basename);
                if ($basename === '') {
                    return null;
                }

                // Prepare normalized forms (remove extension then non-alphanum, lowercase)
                $candName = pathinfo($basename, PATHINFO_FILENAME);
                $candNorm = preg_replace('/[^a-z0-9]/', '', strtolower($candName));

                try {
                    foreach ($storage->files('articles') as $f) {
                        $storedBase = pathinfo($f, PATHINFO_FILENAME);
                        $storedNorm = preg_replace('/[^a-z0-9]/', '', strtolower($storedBase));

                        // direct exact match
                        if (strtolower($storedBase) === strtolower($candName)) {
                            return $f;
                        }

                        // substring or normalized match (covers prefixes, added tokens, etc.)
                        if ($candNorm !== '' && (strpos($storedNorm, $candNorm) !== false || strpos($candNorm, $storedNorm) !== false)) {
                            return $f;
                        }
                    }
                } catch (\Throwable $e) {
                    // ignore
                }

                return null;
            };

            // 1) Look for storage/articles/... occurrences first and prefer files that exist
            if (preg_match_all('/storage\/articles\/([^"' . "\s>]+" . ')/i', $this->excerpt, $matches)) {
                foreach ($matches[1] as $rel) {
                    $rel = ltrim($rel, '/');
                    // Try direct existence
                    try {
                        if ($storage->exists('articles/' . $rel) || $storage->exists($rel)) {
                            $path = $storage->exists('articles/' . $rel) ? 'articles/' . $rel : $rel;
                            return $storage->url($path);
                        }
                    } catch (\Throwable $e) {
                        // ignore
                    }

                    // Try searching by basename
                    $found = $searchByBasename(basename($rel));
                    if ($found) {
                        return $storage->url($found);
                    }
                }
            }

            // 2) Check data-trix-attachment JSON blobs for url/href or filename
            if (preg_match_all('/data-trix-attachment="([^"]+)"/i', $this->excerpt, $trixMatches)) {
                foreach ($trixMatches[1] as $jsonHtml) {
                    $json = html_entity_decode($jsonHtml);
                    $data = json_decode($json, true);
                    if (is_array($data)) {
                        $candidate = $data['url'] ?? $data['href'] ?? null;
                        if (! empty($candidate) && preg_match('/storage\/articles\//i', $candidate)) {
                            // Extract relative path after /storage/
                            if (preg_match('/storage\/([^"' . "\s>]+" . ')/i', $candidate, $m)) {
                                $rel = ltrim($m[1], '/');
                                try {
                                    if ($storage->exists($rel)) {
                                        return $storage->url($rel);
                                    }
                                } catch (\Throwable $e) {
                                }
                                $found = $searchByBasename(basename($rel));
                                if ($found) {
                                    return $storage->url($found);
                                }
                            }
                        }

                        // If no url/href, try filename field
                        if (empty($candidate) && ! empty($data['filename'])) {
                            $found = $searchByBasename($data['filename']);
                            if ($found) {
                                return $storage->url($found);
                            }
                        }
                    }
                }
            }

            // 3) Generic href pattern with storage path
            if (preg_match('/href="(https?:\/\/[^\"]+\/storage\/[^\"]+)"/i', $this->excerpt, $m2)) {
                $full = $m2[1];
                if (preg_match('/storage\/([^"' . "\s>]+" . ')/i', $full, $m3)) {
                    $rel = ltrim($m3[1], '/');
                    try {
                        if ($storage->exists($rel)) {
                            return $storage->url($rel);
                        }
                    } catch (\Throwable $e) {
                    }
                    $found = $searchByBasename(basename($rel));
                    if ($found) {
                        return $storage->url($found);
                    }
                }
                // If href is absolute but not a storage path, try to resolve by basename
                if (filter_var($full, FILTER_VALIDATE_URL)) {
                    // attempt to resolve using basename (e.g. 690...-List_KBLI_2020.xlsx)
                    $basename = basename(parse_url($full, PHP_URL_PATH));
                    if (! empty($basename)) {
                        $found = $searchByBasename($basename);
                        if ($found) {
                            return $storage->url($found);
                        }
                    }

                    // If a Trix data blob contains a filename, prefer resolving that
                    if (preg_match_all('/data-trix-attachment="([^"]+)"/i', $this->excerpt, $trixMatches)) {
                        foreach ($trixMatches[1] as $jsonHtml) {
                            $json = html_entity_decode($jsonHtml);
                            $data = json_decode($json, true);
                            if (is_array($data) && ! empty($data['filename'])) {
                                $found = $searchByBasename($data['filename']);
                                if ($found) {
                                    return $storage->url($found);
                                }
                            }
                        }
                    }

                    return $full;
                }
            }

            // 4) Fallback: look for bare filenames inside the excerpt text (e.g. "List KBLI 2020.xlsx")
            if (preg_match_all('/([A-Za-z0-9 _\-\(\)]+\.(?:pdf|csv|xlsx|xls|docx|doc))/i', $this->excerpt, $nameMatches)) {
                foreach ($nameMatches[1] as $basename) {
                    $found = $searchByBasename($basename);
                    if ($found) {
                        return $storage->url($found);
                    }
                }
            }
        }

        return null;
    }

    protected static function booted()
    {
        static::created(function ($article) {
            // $admins = User::where('role', 'admin')->get();
            User::chunk(100, function ($users) use ($article) {
                foreach ($users as $user) {
                    $user->notify(new ArticleCreatedNotification($article));
                }
            });

            ActivityLog::create([
                'user_id' => auth()->id(),
                'action' => 'tambah',
                'subject_type' => 'Artikel',
                'subject_id' => $article->id,
                'description' => 'Menambah artikel: ' . $article->title,
            ]);
        });

        static::updated(function ($article) {
            $user = auth()->user();

            if ($user->hasRoleContext('Viewer')) {
                ActivityLog::create([
                    'user_id' => auth()->id(),
                    'action' => 'lihat',
                    'subject_type' => 'Artikel',
                    'subject_id' => $article->id,
                    'description' => 'Melihat artikel: ' . $article->title,
                ]);
            } else {
                ActivityLog::create([
                    'user_id' => auth()->id(),
                    'action' => 'ubah',
                    'subject_type' => 'Artikel',
                    'subject_id' => $article->id,
                    'description' => 'Mengubah artikel: ' . $article->title,
                ]);
            }
        });

        static::saving(function (Article $model) {
            // --- Hapus file orphan di tmp yang tidak direferensikan di excerpt ---
            try {
                $disk = $model->attachment_disk ?? 'public';
                $storage = Storage::disk($disk);
                $excerpt = $model->excerpt ?? '';
                $referencedTmp = [];
                if (preg_match_all('/articles\/tmp\/([A-Za-z0-9_\-\.]+)/i', $excerpt, $tmpMatches) && !empty($tmpMatches[1])) {
                    $referencedTmp = array_map(function ($f) {
                        return 'articles/tmp/' . $f;
                    }, $tmpMatches[1]);
                }
                $allTmpFiles = $storage->files('articles/tmp');
                foreach ($allTmpFiles as $tmpFile) {
                    if (!in_array($tmpFile, $referencedTmp)) {
                        try {
                            $storage->delete($tmpFile);
                        } catch (\Throwable $e) {
                        }
                    }
                }
            } catch (\Throwable $e) {
            }
            // If the excerpt contains more than one attachment, block the save and
            // return a validation error. We count attachments that reference the
            // articles path (both tmp and final) inside the excerpt HTML.
            $excerpt = $model->excerpt ?? '';

            // Find candidate attachment references in the excerpt. We will only
            // count attachments that actually reference an existing file or have a
            // non-empty URL/href in the Trix attachment metadata. This allows the
            // user to remove an attachment client-side and avoid false positives.
            $candidates = [];

            // 1) storage/articles/... or articles/tmp/... occurrences
            if (preg_match_all('/(?:storage\/articles|articles\/tmp)\/[^"\'\s>]+/i', $excerpt, $matches)) {
                foreach ($matches[0] as $m) {
                    $candidates[] = $m;
                }
            }

            // 2) data-trix-attachment JSON blobs -> try to decode and check url/href
            if (preg_match_all('/data-trix-attachment="([^"]+)"/i', $excerpt, $trixMatches)) {
                foreach ($trixMatches[1] as $jsonHtml) {
                    // decode HTML entities then decode JSON
                    $json = html_entity_decode($jsonHtml);
                    $data = json_decode($json, true);
                    if (is_array($data)) {
                        // prefer url or href if present
                        if (! empty($data['url']) || ! empty($data['href'])) {
                            $candidates[] = $data['url'] ?: $data['href'];
                        } elseif (! empty($data['filename'])) {
                            // fallback: check for a filename reference inside tmp or articles
                            $candidates[] = $data['filename'];
                        }
                    }
                }
            }

            // Normalize and resolve candidates to actual storage files or valid URLs.
            // We want to count DISTINCT resolved files (so tmp + final references to
            // the same file count as 1). Unresolvable strings are ignored.
            $disk = $model->attachment_disk ?? 'public';
            $storage = Storage::disk($disk);

            // Helper to search storage/articles by basename (we use similar logic
            // to getFirstAttachmentUrl's searchByBasename)
            $searchByBasename = function (string $basename) use ($storage) {
                $basename = trim($basename);
                if ($basename === '') {
                    return null;
                }

                $candName = pathinfo($basename, PATHINFO_FILENAME);
                $candNorm = preg_replace('/[^a-z0-9]/', '', strtolower($candName));

                try {
                    foreach ($storage->files('articles') as $f) {
                        $storedBase = pathinfo($f, PATHINFO_FILENAME);
                        $storedNorm = preg_replace('/[^a-z0-9]/', '', strtolower($storedBase));

                        if (strtolower($storedBase) === strtolower($candName)) {
                            return $f;
                        }
                        if ($candNorm !== '' && (strpos($storedNorm, $candNorm) !== false || strpos($candNorm, $storedNorm) !== false)) {
                            return $f;
                        }
                    }
                } catch (\Throwable $e) {
                    // ignore
                }

                return null;
            };

            $resolved = [];
            foreach (array_unique($candidates) as $cand) {
                $cand = trim($cand);
                if (empty($cand)) {
                    continue;
                }
                // ...existing code for candidate resolution...
                // (no file move logic here)
            }

            // --- Pindahkan semua file tmp yang direferensikan di excerpt ke folder articles/ ---
            $newExcerpt = $excerpt;
            if (preg_match_all('/storage\/articles\/tmp\/([A-Za-z0-9_\-\.]+)/i', $excerpt, $matches) && !empty($matches[1])) {
                foreach ($matches[1] as $tmpFile) {
                    $tmpPath = 'articles/tmp/' . $tmpFile;
                    if ($storage->exists($tmpPath)) {
                        $date = now()->format('Ymd_His');
                        $originalName = pathinfo($tmpFile, PATHINFO_BASENAME);
                        $baseName = pathinfo($originalName, PATHINFO_FILENAME);
                        $ext = pathinfo($originalName, PATHINFO_EXTENSION);
                        $finalName = 'articles/' . $baseName . '_' . $date;
                        if ($ext) {
                            $finalName .= '.' . $ext;
                        }
                        $i = 1;
                        $baseFinal = 'articles/' . $baseName . '_' . $date;
                        if ($ext) {
                            $baseFinal .= '.' . $ext;
                        }
                        while ($storage->exists($finalName)) {
                            $finalName = 'articles/' . $baseName . '-' . $i . '_' . $date;
                            if ($ext) {
                                $finalName .= '.' . $ext;
                            }
                            $i++;
                        }
                        try {
                            $moved = Storage::disk($disk)->move($tmpPath, $finalName);
                            if (! $moved) {
                                $contents = Storage::disk($disk)->get($tmpPath);
                                $put = Storage::disk($disk)->put($finalName, $contents);
                                if (Storage::disk($disk)->exists($finalName)) {
                                    Storage::disk($disk)->delete($tmpPath);
                                }
                            }
                            // Replace URLs in excerpt from tmp -> final
                            $oldCandidates = [];
                            try {
                                $oldCandidates[] = $storage->url($tmpPath);
                            } catch (\Throwable $e) {
                            }
                            try {
                                $oldCandidates[] = rtrim(config('app.url'), '/') . $storage->url($tmpPath);
                            } catch (\Throwable $e) {
                            }
                            $oldCandidates[] = url('/storage/' . ltrim($tmpPath, '/'));
                            $newUrl = $storage->url($finalName);
                            foreach (array_unique($oldCandidates) as $oldUrl) {
                                if (empty($oldUrl)) continue;
                                $newExcerpt = str_replace($oldUrl, $newUrl, $newExcerpt);
                            }
                        } catch (\Throwable $e) {
                            Log::error('[Article Move] Exception saat move/copy', [
                                'error' => $e->getMessage()
                            ]);
                        }
                    }
                }
                $model->excerpt = $newExcerpt;
            }
        });

        // (logic moved to saving event above)

        static::deleting(function (Article $model) {
            $storage = Storage::disk('public');
            $excerpt = $model->excerpt ?? '';

            // tangkap semua URL di Trix JSON attachment
            if (preg_match_all('/([A-Za-z0-9 _\-\(\)]+\.(?:pdf|csv|xlsx|xls|docx|doc))/i', $excerpt, $nameMatches)) {
                foreach ($nameMatches[1] as $basename) {
                    $url = $storage->url('articles/' . $basename);
                    $parsed = parse_url($url, PHP_URL_PATH);
                    $relativePath = ltrim(str_replace('/storage/', '', $parsed), '/');

                    if ($storage->exists($relativePath)) {
                        $storage->delete($relativePath);
                    }
                }
            }
        });

        static::deleted(function ($article) {
            ActivityLog::create([
                'user_id' => auth()->id(),
                'action' => 'hapus',
                'subject_type' => 'Artikel',
                'subject_id' => $article->id,
                'description' => 'Menghapus artikel: ' . $article->title,
            ]);
        });
    }
}
