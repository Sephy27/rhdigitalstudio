<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Vich\UploaderBundle\Event\Event;
use Vich\UploaderBundle\Event\Events;
use Spatie\ImageOptimizer\OptimizerChainFactory;

class ImageOptimizeSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [Events::POST_UPLOAD => 'onPostUpload'];
    }

    public function onPostUpload(Event $event): void
    {
        $mapping = $event->getMapping();
        $object  = $event->getObject();

        // Facultatif : ne traiter qu’un mapping spécifique (ex: "product_image")
        if ($mapping->getMappingName() !== 'product_image') {
            return;
        }

        $fileName = $mapping->getFileName($object);
        if (!$fileName) {
            return;
        }

        // Chemin absolu du fichier uploadé (ex: /app/public/uploads/images/xxx.jpg)
        $path = rtrim($mapping->getUploadDestination(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $fileName;
        if (!is_file($path)) {
            return;
        }

        // Optimisation lossless (jpegoptim, optipng, etc. si disponibles sur le serveur)
        $optimizerChain = OptimizerChainFactory::create();
        $optimizerChain->optimize($path);
    }
}
