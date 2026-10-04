<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Storage;

class HrBlastEmail extends Mailable
{
    public function __construct(public string $emailSubject, public string $messageText, public array $files = []) {}

    public function build()
    {
        foreach ($this->files as $file) {
            if (! Storage::disk('hr_email_private')->exists($file['path'])) {
                throw new \RuntimeException('Lampiran email tidak ditemukan.');
            }
            $this->attachFromStorageDisk('hr_email_private', $file['path'], $file['name'], ['mime' => $file['mime']]);
        }
        return $this->subject($this->emailSubject)->view('emails.hr-blast', [
            'messageHtml' => self::sanitizeMessage($this->messageText),
        ]);
    }

    public static function sanitizeMessage(string $message): string
    {
        if ($message === strip_tags($message)) {
            return nl2br(e($message));
        }
        $document = new \DOMDocument();
        $previous = libxml_use_internal_errors(true);
        try {
            $document->loadHTML('<meta charset="UTF-8"><body>'.$message.'</body>', LIBXML_NONET);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
        // Rebuild allowed formatting without attributes, URLs, styles, or active content.
        $render = function (\DOMNode $node) use (&$render): string {
            if ($node instanceof \DOMText) {
                return e($node->textContent);
            }
            if (! $node instanceof \DOMElement || in_array(strtolower($node->nodeName), ['script', 'style', 'iframe', 'object', 'embed', 'svg', 'math', 'template'], true)) {
                return '';
            }
            $content = '';
            foreach ($node->childNodes as $child) {
                $content .= $render($child);
            }
            $tag = strtolower($node->nodeName);
            if ($tag === 'br') {
                return '<br>';
            }
            return in_array($tag, ['p', 'strong', 'b', 'em', 'i', 'u', 's', 'ul', 'ol', 'li', 'blockquote', 'h2', 'h3'], true)
                ? "<{$tag}>{$content}</{$tag}>" : $content;
        };

        return $render($document->getElementsByTagName('body')->item(0));
    }
}
