<?php

/*
 * This file is part of the Keyclic WebApiExtension.
 *
 * (c) Keyclic team <techies@keyclic.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Client\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class DefaultController
{
    /**
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function echo(Request $request)
    {
        $return = [
           'warning' => 'Do not expose this service in production : it is intrinsically unsafe',
           'method' => $request->getMethod(),
       ];

        // Forms should be read from request, other data straight from input.
        $requestData = $request->request->all();
        $return = array_merge(
           $return,
           $requestData
       );

        /** @var string $content */
        $content = $request->getContent(false);
        if (false === empty($content)) {
            $data = json_decode($content, true);

            if (false === is_array($data)) {
                $data['content'] = $data;
            }

            $return = array_merge(
               $return,
               $data
           );
        }

        $return['headers'] = $request->headers->all();
        $return['query'] = $request->query->all();

        return new JsonResponse($return);
    }

    /**
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function error(Request $request)
    {
        $response = $this->echo($request);
        $response->setStatusCode(Response::HTTP_NOT_FOUND);

        return $response;
    }
}
