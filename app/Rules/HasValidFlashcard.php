<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class HasValidFlashcard implements Rule
{
    private $message;

    private const VALID_CAPTION_LANGUAGES = [
        'en', 'es', 'fr', 'de', 'zh', 'ja', 'ko', 'pt', 'ar', 'hi', 'ru', 'it'
    ];

    public function __construct()
    {
        //
    }

    public function passes($attribute, $value)
    {
        $data = json_decode($value, true);

        $errors = [
            'term'                 => '',
            'answer'               => '',
            'front'                => '',
            'back'                 => '',
            'frontMediaS3Key'      => '',
            'backMediaS3Key'       => '',
            'frontMediaAlt'        => '',
            'backMediaAlt'         => '',
            'frontCaptionLanguage' => '',
            'backCaptionLanguage'  => '',
        ];

        if (!$data || !isset($data['card'])) {
            $this->message = json_encode($errors);
            return false;
        }

        $card = $data['card'];
        $passes = true;

        // Validate front
        $frontType = $card['frontType'] ?? '';
        switch ($frontType) {
            case 'text_only':
                if (empty(trim($card['term'] ?? ''))) {
                    $errors['term'] = 'A term is required.';
                    $passes = false;
                }
                break;

            case 'text_media':
                if (empty(trim($card['term'] ?? ''))) {
                    $errors['term'] = 'A term is required.';
                    $passes = false;
                }
                if (empty($card['frontMediaS3Key'] ?? '')) {
                    $errors['frontMediaS3Key'] = 'Please upload an image or video.';
                    $passes = false;
                } elseif (($card['frontMediaType'] ?? '') === 'image') {
                    $passes = $this->validateImageAlt($card, 'front', $errors) && $passes;
                } elseif (($card['frontMediaType'] ?? '') === 'video') {
                    $passes = $this->validateCaptionLanguage($card, 'front', $errors) && $passes;
                }
                break;

            case 'free_form':
                if (empty(trim(strip_tags($card['front'] ?? '')))) {
                    $errors['front'] = 'Front content is required.';
                    $passes = false;
                }
                break;

            case 'media':
                if (empty($card['frontMediaS3Key'] ?? '')) {
                    $errors['frontMediaS3Key'] = 'Please upload an image or video.';
                    $passes = false;
                } elseif (($card['frontMediaType'] ?? '') === 'image') {
                    $passes = $this->validateImageAlt($card, 'front', $errors) && $passes;
                } elseif (($card['frontMediaType'] ?? '') === 'video') {
                    $passes = $this->validateCaptionLanguage($card, 'front', $errors) && $passes;
                }
                break;

            default:
                $errors['front'] = 'The front type is not valid.';
                $passes = false;
        }

        // Validate back
        $backType = $card['backType'] ?? '';
        switch ($backType) {
            case 'text_only':
                if (empty(trim($card['answer'] ?? ''))) {
                    $errors['answer'] = 'An answer is required.';
                    $passes = false;
                }
                break;

            case 'text_media':
                if (empty(trim($card['answer'] ?? ''))) {
                    $errors['answer'] = 'An answer is required.';
                    $passes = false;
                }
                if (empty($card['backMediaS3Key'] ?? '')) {
                    $errors['backMediaS3Key'] = 'Please upload an image or video.';
                    $passes = false;
                } elseif (($card['backMediaType'] ?? '') === 'image') {
                    $passes = $this->validateImageAlt($card, 'back', $errors) && $passes;
                } elseif (($card['backMediaType'] ?? '') === 'video') {
                    $passes = $this->validateCaptionLanguage($card, 'back', $errors) && $passes;
                }
                break;

            case 'free_form':
                if (empty(trim(strip_tags($card['back'] ?? '')))) {
                    $errors['back'] = 'Back content is required.';
                    $passes = false;
                }
                break;

            case 'media':
                if (empty($card['backMediaS3Key'] ?? '')) {
                    $errors['backMediaS3Key'] = 'Please upload an image or video.';
                    $passes = false;
                } elseif (($card['backMediaType'] ?? '') === 'image') {
                    $passes = $this->validateImageAlt($card, 'back', $errors) && $passes;
                } elseif (($card['backMediaType'] ?? '') === 'video') {
                    $passes = $this->validateCaptionLanguage($card, 'back', $errors) && $passes;
                }
                break;

            default:
                $errors['back'] = 'The back type is not valid.';
                $passes = false;
        }

        $this->message = json_encode($errors);
        return $passes;
    }

    /**
     * Alt text is mandatory unless marked decorative, and capped at 150 characters.
     * Figure caption and long description are optional — no validation needed.
     */
    private function validateImageAlt(array $card, string $side, array &$errors): bool
    {
        if ($card["{$side}MediaDecorative"] ?? false) {
            return true;
        }

        $alt = trim($card["{$side}MediaAlt"] ?? '');

        if (empty($alt)) {
            $errors["{$side}MediaAlt"] = 'Alt text is required for accessibility.';
            return false;
        }

        if (mb_strlen($alt) > 150) {
            $errors["{$side}MediaAlt"] = 'Alt text must be 150 characters or fewer.';
            return false;
        }

        return true;
    }

    /**
     * Validate that a recognised caption language has been selected for a video.
     */
    private function validateCaptionLanguage(array $card, string $side, array &$errors): bool
    {
        $lang = $card["{$side}CaptionLanguage"] ?? '';

        if (empty($lang) || !in_array($lang, self::VALID_CAPTION_LANGUAGES)) {
            $errors["{$side}CaptionLanguage"] = 'Please select a valid caption language.';
            return false;
        }

        return true;
    }

    public function message()
    {
        return $this->message;
    }
}
