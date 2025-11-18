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
            User::where('articleupdate', 'Y')->chunk(100, function ($users) use ($article) {
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

            $disk = $model->attachment_disk ?? 'public';
            $storage = Storage::disk($disk);

            $excerpt = $model->excerpt ?? '';
            $newExcerpt = $excerpt;

            $tmpPattern = '/(?:https?:\/\/[^\s"]+)?\/?storage\/articles\/tmp\/([^"\'\s>]+)
                  |articles\/tmp\/([^"\'\s>]+)/ix';

            preg_match_all($tmpPattern, $excerpt, $matches);

            // Gabungkan hasil match group 1 & 2
            $tmpFiles = array_filter(array_merge(
                $matches[1] ?? [],
                $matches[2] ?? []
            ));

            if (empty($tmpFiles)) {
                return;
            }

            foreach ($tmpFiles as $tmpFileName) {

                $tmpRelative = "articles/tmp/" . $tmpFileName;

                // PASTIKAN FILE ADA
                if (!$storage->exists($tmpRelative)) {
                    continue;
                }

                $ext = pathinfo($tmpFileName, PATHINFO_EXTENSION);
                $base = pathinfo($tmpFileName, PATHINFO_FILENAME);

                $date = now()->format('Ymd_His');
                $finalRelative = "articles/{$base}_{$date}." . $ext;

                // jika bentrok → increment
                $i = 1;
                while ($storage->exists($finalRelative)) {
                    $finalRelative = "articles/{$base}_{$date}_{$i}." . $ext;
                    $i++;
                }

                try {
                    $tmpFull = $storage->path($tmpRelative);
                    $finalFull = $storage->path($finalRelative);

                    // Native rename (lebih cepat)
                    $moved = @rename($tmpFull, $finalFull);

                    if (!$moved) {
                        // fallback copy
                        $content = $storage->get($tmpRelative);
                        $storage->put($finalRelative, $content);
                        $storage->delete($tmpRelative);
                    }
                } catch (\Throwable $e) {
                    continue;
                }

                $newUrl = $storage->url($finalRelative);

                $oldPatterns = [
                    "articles/tmp/$tmpFileName",
                    "/storage/articles/tmp/$tmpFileName",
                    url("/storage/articles/tmp/$tmpFileName"),
                ];

                foreach ($oldPatterns as $old) {
                    if (!empty($old)) {
                        $newExcerpt = str_replace($old, $newUrl, $newExcerpt);
                    }
                }

                // Update juga filename, url, dan href di data-trix-attachment JSON
                $newFileName = basename($finalRelative);
                // Ganti "filename":"$tmpFileName" menjadi nama file baru
                $newExcerpt = preg_replace(
                    '/("filename"\s*:\s*")' . preg_quote($tmpFileName, '/') . '(")/i',
                    '$1' . $newFileName . '$2',
                    $newExcerpt
                );
                // Ganti "url":"...tmp..." dan "href":"...tmp..." menjadi $newUrl
                $newExcerpt = preg_replace(
                    '/("url"\s*:\s*")([^"\n]*' . preg_quote($tmpFileName, '/') . ')(")/i',
                    '$1' . $newUrl . '$3',
                    $newExcerpt
                );
                $newExcerpt = preg_replace(
                    '/("href"\s*:\s*")([^"\n]*' . preg_quote($tmpFileName, '/') . ')(")/i',
                    '$1' . $newUrl . '$3',
                    $newExcerpt
                );
            }

            $model->excerpt = $newExcerpt;
        });

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
