<?php

namespace Emergence\Embeds;

class Classifier
{
    public static $providers = [];

    public static function __classLoaded()
    {
        // configure stock providers
        if (!isset(static::$providers['youtube'])) {
            static::$providers['youtube'] = [
                'pattern' => '{'.
                    '^https?://'.
                    '('.
                        // long youtube URLs
                        '(www\.)?youtube\.com/'.
                        '('.
                            'embed/(?<videoIdEmbed>[^/?#\s]+)'.
                            '|'.
                            'watch\?(.+&)?v=(?<videoIdWatch>[^&#\s]+)'.
                            '|'.
                            'user/[^#]+#.*/1/(?<videoIdUser>[^/\s]+)'.
                            '|'.
                            'v/(?<videoId>[^/?#\s]+)'.
                        ')'.
                    '|'.
                        // short youtube URLs
                        'youtu\.be/(?<videoIdShort>[^/?#\s]+)'.
                    ')'.
                '}i',
                'output' => [
                    'videoId' => ['videoId', 'videoIdShort', 'videoIdUser', 'videoIdWatch', 'videoIdEmbed']
                ],
                'getEmbedHtml' => function ($data) {
                    if (empty($data['videoId'])) {
                        return null;
                    }

                    return '<iframe class="embed-iframe embed-youtube" src="https://www.youtube.com/embed/'.htmlspecialchars($data['videoId']).'" frameborder="0"></iframe>';
                }
            ];
        }
    }

    public static function analyzeUrl($url)
    {
        foreach (static::$providers AS $providerId => $provider) {
            $patterns = is_string($provider['pattern']) ? [$provider['pattern']] : $provider['pattern'];

            foreach ($patterns AS $pattern) {
                if (!preg_match($pattern, $url, $matches)) {
                    continue;
                }

                $data = [
                    'provider' => $providerId,
                    'url' => $url
                ];

                if (!empty($provider['output'])) {
                    foreach ($provider['output'] AS $outputKey => $inputKey) {
                        $inputKeys = is_string($inputKey) ? [$inputKey] : $inputKey;

                        foreach ($inputKeys AS $inputKey) {
                            if (!empty($matches[$inputKey])) {
                                $data[$outputKey] = $matches[$inputKey];
                                break;
                            }
                        }
                    }
                }

                return $data;
            }
        }

        return false;
    }

    public static function getEmbedHtml(array $data)
    {
        if (empty($data['provider'])) {
            return '';
        }

        if (!empty(static::$providers[$data['provider']]) && is_callable(static::$providers[$data['provider']]['getEmbedHtml'])) {
            if ($html = call_user_func(static::$providers[$data['provider']]['getEmbedHtml'], $data)) {
                return $html;
            }
        }

        if (empty($data['url'])) {
            return '';
        }

        return '<a href="'.htmlspecialchars($data['url']).'">'.htmlspecialchars($data['title']?:$data['url']).'</a>';
    }
}

/**
    $youtubeTestUrls = [
        'https://www.youtube.com/watch?v=0zM3nApSvMg&feature=feedrec_grec_index',
        'https://www.youtube.com/user/IngridMichaelsonVEVO#p/a/u/1/QdK8U-VIH_o',
        'https://www.youtube.com/v/0zM3nApSvMg?fs=1&amp;hl=en_US&amp;rel=0',
        'https://www.youtube.com/watch?v=0zM3nApSvMg#t=0m10s',
        'https://www.youtube.com/embed/0zM3nApSvMg?rel=0',
        'https://www.youtube.com/watch?v=0zM3nApSvMg',
        'https://www.youtube.com/watch?foo=bar&v=0zM3nApSvMg',
        'https://youtu.be/0zM3nApSvMg',
        'https://www.youtu.be/0zM3nApSvMg'
    ];
*/