<?php

/**
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; under version 2
 * of the License (non-upgradable).
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * Copyright (c) 2020 (original work) Open Assessment Technologies SA;
 */

declare(strict_types=1);

namespace App\Action\Platform\Message;

use App\Form\Platform\Message\LtiResourceLinkLaunchType;
use OAT\Library\Lti1p3Core\Message\Launch\Builder\LtiResourceLinkLaunchRequestBuilder;
use OAT\Library\Lti1p3Core\Resource\LtiResourceLink\LtiResourceLink;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Twig\Environment;

class LtiResourceLinkLaunchAction
{
    /** @var FlashBagInterface */
    private $flashBag;

    /** @var Environment */
    private $twig;

    /** @var FormFactoryInterface */
    private $factory;

    /** @var LtiResourceLinkLaunchRequestBuilder */
    private $builder;

    public function __construct(
        FlashBagInterface $flashBag,
        Environment $twig,
        FormFactoryInterface $factory,
        LtiResourceLinkLaunchRequestBuilder $builder
    ) {
        $this->flashBag = $flashBag;
        $this->twig = $twig;
        $this->factory = $factory;
        $this->builder = $builder;
    }

    public function __invoke(Request $request): Response
    {
        $form = $this->factory->create(LtiResourceLinkLaunchType::class);

        $form->handleRequest($request);

        $user = null;
        $claims = [];
        $ltiResourceLinkLaunchRequest = null;

        if ($form->isSubmitted() && $form->isValid()) {

            $formData = $form->getData();

            $resourceLink = new LtiResourceLink(
                Uuid::uuid4()->toString(),
                [
                    'url' => $formData['launch_url'] ?? null
                ]
            );

            if ($formData['claims']) {
                $claims = json_decode($formData['claims'], true);

                if (JSON_ERROR_NONE !== json_last_error()) {
                    throw new BadRequestHttpException(sprintf('json_decode error: %s', json_last_error_msg()));
                }
            }

            $ltiResourceLinkLaunchRequest = $this->builder->buildLtiResourceLinkLaunchRequest(
                $resourceLink,
                $formData['registration'],
                $formData['user'] ?? 'anonymous',
                null,
                [],
                $claims
            );

            $this->flashBag->add('success', 'LTI resource link generated with success');
        }

        return new Response(
            $this->twig->render(
                'platform/message/ltiResourceLinkLaunch.html.twig',
                [
                 'form' => $form->createView(),
                    'ltiResourceLinkLaunchRequest' => $ltiResourceLinkLaunchRequest
                ]
            )
        );
    }
}