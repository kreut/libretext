<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class HasValidFlashcard implements Rule
{
    private $message;

    public function __construct()
    {
        //
    }

    public function passes($attribute, $value)
    {
        $data = json_decode($value, true);

        $errors = [
            'term'           => '',
            'answer'         => '',
            'front'          => '',
            'back'           => '',
            'frontMediaS3Key' => '',
            'backMediaS3Key'  => '',
            'frontMediaAlt'   => '',
            'backMediaAlt'    => '',
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
                }
                if (!empty($card['frontMediaS3Key']) && ($card['frontMediaType'] ?? '') === 'image' && empty(trim($card['frontMediaAlt'] ?? ''))) {
                    $errors['frontMediaAlt'] = 'Alt text is required for accessibility.';
                    $passes = false;
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
                }
                if (!empty($card['frontMediaS3Key']) && ($card['frontMediaType'] ?? '') === 'image' && empty(trim($card['frontMediaAlt'] ?? ''))) {
                    $errors['frontMediaAlt'] = 'Alt text is required for accessibility.';
                    $passes = false;
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
                }
                if (!empty($card['backMediaS3Key']) && ($card['backMediaType'] ?? '') === 'image' && empty(trim($card['backMediaAlt'] ?? ''))) {
                    $errors['backMediaAlt'] = 'Alt text is required for accessibility.';
                    $passes = false;
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
                }
                if (!empty($card['backMediaS3Key']) && ($card['backMediaType'] ?? '') === 'image' && empty(trim($card['backMediaAlt'] ?? ''))) {
                    $errors['backMediaAlt'] = 'Alt text is required for accessibility.';
                    $passes = false;
                }
                break;

            default:
                $errors['back'] = 'The back type is not valid.';
                $passes = false;
        }

        $this->message = json_encode($errors);
        return $passes;
    }

    public function message()
    {
        return $this->message;
    }
}
