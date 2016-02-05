<?php
/**
 * Created by PhpStorm.
 * User: joost
 * Date: 04-02-16
 * Time: 21:55
 */

namespace AppBundle\EventListener;

use Doctrine\ORM\Event\LifecycleEventArgs;
use AppBundle\Entity\Post;
use Embedly\Embedly;

class PostSaver
{
    public function postPersist(LifecycleEventArgs $args)
    {
        $entity = $args->getEntity();

        // only act on some "Post" entity
        if (!$entity instanceof Post) {
            return;
        }

        $entityManager = $args->getEntityManager();

        // First via embed API
        $api = new Embedly(array(
            'key' => '17ba4297ae614c5f82c7f047286ae521',
            'user_agent' => 'Mozilla/5.0 (compatible; mytestapp/1.0)'
        ));

        // Request a thumbnail from Embed.ly api
        $meta = $this->getMetaData($api, $entity->getUrl());

        $entity->setImage($meta['thumbnail']);

        if (empty($entity->getTitle())) {
            $entity->setTitle($meta['title']);
        }

        // save the host
        $entity->setHost(parse_url($entity->getUrl(), PHP_URL_HOST));

        $entityManager->persist($entity);
        $entityManager->flush($entity);
    }

    private function getMetaData($api, $url)
    {
        $meta['title'] = '';
        $meta['thumbnail'] = '';

        $objs = $api->oembed($url);

        if (isset($objs->thumbnail_url) && !empty($objs->thumbnail_url)) {
            $meta['thumbnail'] = $objs->thumbnail_url;
        }

        if (isset($objs->title) && !empty($objs->title)) {
            $meta['title'] = $objs->title;
        }

        // @TODO Second try via extract API
        return $meta;

    }
}