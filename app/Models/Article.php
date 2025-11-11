<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasAuditTrail;
use App\Models\Category;
use App\Notifications\ArticleCreatedNotification;
use Illuminate\Support\Facades\Storage;
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
        });

        static::saving(function (Article $model) {
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

                // If it's an absolute URL, try to extract storage path or basename
                if (preg_match('/^https?:\/\//i', $cand) && filter_var($cand, FILTER_VALIDATE_URL)) {
                    // If it contains /storage/, extract relative path after /storage/
                    if (preg_match('/storage\/([^"' . "\s>]+" . ')/i', $cand, $m)) {
                        $rel = ltrim($m[1], '/');
                        try {
                            if ($storage->exists($rel)) {
                                $resolved[] = $rel;
                                continue;
                            }
                        } catch (\Throwable $e) {
                        }
                        $found = $searchByBasename(basename($rel));
                        if ($found) {
                            $resolved[] = $found;
                            continue;
                        }
                    }

                    // Fallback: try to resolve by basename from the URL
                    $basename = basename(parse_url($cand, PHP_URL_PATH));
                    if (! empty($basename)) {
                        $found = $searchByBasename($basename);
                        if ($found) {
                            $resolved[] = $found;
                            continue;
                        }
                    }

                    // If absolute URL but not resolvable to storage, treat it as a distinct external URL
                    $resolved[] = $cand;
                    continue;
                }

                // Normalize storage path: remove leading 'storage/' if present
                $p = preg_replace('/^storage\//i', '', $cand);
                $p = ltrim($p, '/');

                try {
                    if ($storage->exists($p)) {
                        $resolved[] = $p;
                        continue;
                    }
                } catch (\Throwable $e) {
                    // ignore
                }

                // Try to resolve by basename
                $found = $searchByBasename(basename($cand));
                if ($found) {
                    $resolved[] = $found;
                    continue;
                }
            }

            // Keep only unique resolved targets
            $resolved = array_values(array_unique($resolved));
            $count = count($resolved);

            if ($count > 1) {
                // If there are temporary files referenced, try to delete them to avoid
                // leaving orphaned uploads when we block the save.
                try {
                    $disk = $model->attachment_disk ?? 'public';
                    $storage = Storage::disk($disk);
                    if (isset($matches[0]) && is_array($matches[0])) {
                        foreach ($matches[0] as $m) {
                            // only delete tmp paths
                            if (str_contains($m, 'articles/tmp/')) {
                                $p = preg_replace('/^storage\//', '', $m);
                                try {
                                    if ($storage->exists($p)) {
                                        $storage->delete($p);
                                    }
                                } catch (\Throwable $e) {
                                }
                            }
                        }
                    }
                } catch (\Throwable $e) {
                    // ignore cleanup failures
                }

                // Build a validator instance so the ValidationException contains proper
                // error bags that Filament/Livewire can display on the form. Provide
                // both 'excerpt' and 'data.excerpt' keys to cover different form paths.
                $validator = \Illuminate\Support\Facades\Validator::make([], []);
                $validator->errors()->add('excerpt', 'Hanya diperbolehkan 1 dokumen per artikel. Hapus dokumen tambahan sebelum menyimpan.');
                $validator->errors()->add('data.excerpt', 'Hanya diperbolehkan 1 dokumen per artikel. Hapus dokumen tambahan sebelum menyimpan.');

                throw new \Illuminate\Validation\ValidationException($validator);
            }

            // If an attachment is present, populate metadata (filename/mime/size) but do NOT modify excerpt.
            if ($model->attachment) {
                $disk = $model->attachment_disk ?? 'public';

                // Populate some metadata if not set (stored filename, mime, size)
                if (empty($model->attachment_original_name)) {
                    $model->attachment_original_name = basename($model->attachment);
                }

                if (empty($model->attachment_mime)) {
                    try {
                        $model->attachment_mime = Storage::disk($disk)->mimeType($model->attachment);
                    } catch (\Exception $e) {
                        // ignore
                    }
                }

                if (empty($model->attachment_size)) {
                    try {
                        $model->attachment_size = Storage::disk($disk)->size($model->attachment);
                    } catch (\Exception $e) {
                        // ignore
                    }
                }
            }
        });

        // After the model is saved we finalize any temporary uploaded attachment
        // that was uploaded into `articles/tmp/*` by moving it to `articles/{slug}.{ext}`
        // and updating the model's attachment metadata. We also enforce a single
        // attachment by keeping only the first one found and removing others.
        static::saved(function (Article $model) {
            $disk = $model->attachment_disk ?? 'public';
            $storage = Storage::disk($disk);

            $excerpt = $model->excerpt ?? '';

            // 1) If there are any temporary uploaded files referenced in the excerpt
            //    under storage/articles/tmp/, move the first to final location and
            //    delete any other temporary uploads that may have been inserted.
            if (preg_match_all('/storage\/articles\/tmp\/([A-Za-z0-9_\-\.]+)/i', $excerpt, $matches) && ! empty($matches[1])) {
                $first = $matches[1][0];
                $tmpPath = 'articles/tmp/' . $first;

                if ($storage->exists($tmpPath)) {
                    $ext = pathinfo($first, PATHINFO_EXTENSION);
                    $slug = Str::slug($model->title ?: ('article-' . $model->id));
                    $base = 'articles/' . $slug;
                    $finalName = $base . ($ext ? '.' . $ext : '');

                    // Ensure unique filename if collision
                    $i = 1;
                    $final = $finalName;
                    while ($storage->exists($final)) {
                        $final = $base . '-' . $i . ($ext ? '.' . $ext : '');
                        $i++;
                    }

                    try {
                        // Try native move first
                        $moved = false;
                        try {
                            $moved = $storage->move($tmpPath, $final);
                        } catch (\Throwable $e) {
                            $moved = false;
                        }

                        // If move didn't succeed, fallback to copy+delete
                        if (! $moved) {
                            try {
                                $contents = $storage->get($tmpPath);
                                $storage->put($final, $contents);
                                // ensure written before deleting
                                if ($storage->exists($final)) {
                                    $storage->delete($tmpPath);
                                }
                            } catch (\Throwable $e) {
                                // ignore failures here; we'll attempt to delete below if needed
                            }
                        }

                        // If tmp still exists for some reason, try to delete it
                        try {
                            if ($storage->exists($tmpPath)) {
                                $storage->delete($tmpPath);
                            }
                        } catch (\Throwable $e) {
                            // ignore
                        }

                        // Replace URLs in excerpt from tmp -> final. Try multiple URL variants
                        $newUrl = $storage->url($final);
                        $oldCandidates = [];
                        try {
                            $oldCandidates[] = $storage->url($tmpPath);
                        } catch (\Throwable $e) {
                        }
                        // absolute url with app url
                        try {
                            $oldCandidates[] = rtrim(config('app.url'), '/') . $storage->url($tmpPath);
                        } catch (\Throwable $e) {
                        }
                        // relative storage path
                        $oldCandidates[] = url('/storage/' . ltrim($tmpPath, '/'));

                        $newExcerpt = $excerpt;
                        foreach (array_unique($oldCandidates) as $oldUrl) {
                            if (empty($oldUrl)) continue;
                            $newExcerpt = str_replace($oldUrl, $newUrl, $newExcerpt);
                        }
                    } catch (\Exception $e) {
                        $newExcerpt = $excerpt;
                    }

                    // Delete any other temporary attachments referenced in the excerpt
                    if (count($matches[1]) > 1) {
                        for ($k = 1; $k < count($matches[1]); $k++) {
                            $other = 'articles/tmp/' . $matches[1][$k];
                            try {
                                if ($storage->exists($other)) {
                                    $storage->delete($other);
                                }
                            } catch (\Exception $e) {
                                // ignore
                            }
                            try {
                                $oldOtherUrl = $storage->url('articles/tmp/' . $matches[1][$k]);
                                $newExcerpt = str_replace($oldOtherUrl, '', $newExcerpt);
                            } catch (\Exception $e) {
                                // ignore
                            }
                        }
                    }

                    // Update model metadata and excerpt without firing events to avoid loops
                    // $model->attachment = $final;
                    // $model->attachment_original_name = $first;
                    // try {
                    //     $model->attachment_mime = $storage->mimeType($final);
                    // } catch (\Exception $e) {
                    //     // ignore
                    // }
                    // try {
                    //     $model->attachment_size = $storage->size($final);
                    // } catch (\Exception $e) {
                    //     // ignore
                    // }
                    // $model->attachment_disk = $disk;
                    $model->excerpt = $newExcerpt;

                    $model->saveQuietly();
                }
            }

            // 2) If an attachment already exists and the article title changed such
            //    that the filename should reflect the new title, attempt to rename
            //    the existing stored file to keep filenames aligned with title.
            if (! empty($model->attachment) && ! str_contains($model->attachment, 'tmp') && $storage->exists($model->attachment)) {
                $current = $model->attachment;
                $ext = pathinfo($current, PATHINFO_EXTENSION);
                $slug = Str::slug($model->title ?: ('article-' . $model->id));
                $desiredBase = 'articles/' . $slug;
                $desired = $desiredBase . ($ext ? '.' . $ext : '');

                if ($current !== $desired) {
                    $i = 1;
                    $finalDesired = $desired;
                    while ($storage->exists($finalDesired)) {
                        $finalDesired = $desiredBase . '-' . $i . ($ext ? '.' . $ext : '');
                        $i++;
                    }

                    try {
                        $storage->move($current, $finalDesired);
                        // Replace URLs in excerpt
                        try {
                            $oldUrl = $storage->url($current);
                            $newUrl = $storage->url($finalDesired);
                            $model->excerpt = str_replace($oldUrl, $newUrl, $model->excerpt);
                        } catch (\Exception $e) {
                            // ignore
                        }

                        $model->attachment = $finalDesired;
                        $model->saveQuietly();
                    } catch (\Exception $e) {
                        // ignore move errors
                    }
                }
            }
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
    }
}
