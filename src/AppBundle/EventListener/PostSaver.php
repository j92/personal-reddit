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
use Symfony\Component\DependencyInjection\ContainerInterface;

class PostSaver
{
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function postPersist(LifecycleEventArgs $args)
    {
        $entity = $args->getEntity();

        // only act on some "Post" entity
        if (!$entity instanceof Post) {
            return;
        }

        $entityManager = $args->getEntityManager();

        $embed_config = $this->container->getParameter('embedly');

        if ( empty($embed_config) ){
            return;
        }

        // First via embed API
        $api = new Embedly(array(
            'key' => $embed_config['key'],
            'user_agent' => $embed_config['user_agent']
        ));

        // Request a thumbnail from Embed.ly api
        $meta = $this->getMetaData($api, $entity->getUrl());

        $entity->setImage($meta['thumbnail']);

        if (!empty($meta['thumbnail'])) {

            $cloud_config = $this->container->getParameter('cloudinary');

            if (isset($cloud_config) &&
                !empty($cloud_config['cloud_name']) &&
                !empty($cloud_config['api_key']) &&
                !empty($cloud_config['api_secret'] )
            ) {
                // download and copy to cdn
                \Cloudinary::config(array(
                    'cloud_name' => $cloud_config['cloud_name'],
                    'api_key' => $cloud_config['api_key'],
                    'api_secret' => $cloud_config['api_secret']
                ));

                $result = \Cloudinary\Uploader::upload(
                    $meta['thumbnail'], array(
                        "crop" => "limit", "width" => "100", "height" => "100"
                    )
                );

                if (isset($result['url'])) {
                    $entity->setImage($result['url']);
                }
            }

        }

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

        if (!empty($objs->thumbnail_url)) {
            $meta['thumbnail'] = $objs->thumbnail_url;
        }

        if (!empty($objs->title)) {
            $meta['title'] = $objs->title;
        }
//
//        if (empty($meta['thumbnail']) || empty($meta['thumbnail'])) {
//            $objs = $api->extract(array(
//                'urls' => array(
//                    $url
//                )
//            ));
//
//            if (isset($objs->thumbnail_url) && !empty($objs->thumbnail_url)) {
//                $meta['thumbnail'] = $objs->thumbnail_url;
//            }
//
//            if (isset($objs->title) && !empty($objs->title)) {
//                $meta['title'] = $objs->title;
//            }
//        }



        return $meta;

    }
}