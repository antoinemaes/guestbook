<?php


namespace App\Serializer;


use App\Entity\Comment;
use Symfony\Component\HttpFoundation\UrlHelper;
use Symfony\Component\Serializer\Normalizer\ContextAwareNormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Vich\UploaderBundle\Templating\Helper\UploaderHelper;

class CommentNormalizer implements ContextAwareNormalizerInterface
{
    private $normalizer;
    private $url;
    private $uploader;

    public function __construct(ObjectNormalizer $normalizer, UrlHelper $url, UploaderHelper $uploader)
    {
        $this->normalizer = $normalizer;
        $this->url=$url;
        $this->uploader=$uploader;
    }

    /**
     * @inheritDoc
     */
    public function supportsNormalization($data, string $format = null, array $context = [])
    {
        return $data instanceof Comment;
    }

    /**
     * @inheritDoc
     */
    public function normalize($comment, string $format = null, array $context = [])
    {
        $data = $this->normalizer->normalize($comment, $format, $context);
        $relative = $this->uploader->asset($comment);
        $data['photoUrl'] =
            $relative ?
                $this->url->getAbsoluteUrl($relative) :
                null;
        return $data;
    }

}