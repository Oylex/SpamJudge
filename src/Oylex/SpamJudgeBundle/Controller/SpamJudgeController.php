<?php

namespace Oylex\SpamJudgeBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

use Oylex\SpamJudgeBundle\Entity\TokenCount;
use Oylex\SpamJudgeBundle\Entity\TextSample;
use Oylex\SpamJudgeBundle\Form\TextSampleType;

/**
 * Class SpamJudgeController
 *
 * @package Oylex\SpamJudgeBundle\Controller
 * @todo: limit the spamicity sample to the 15 most representatives values
 * @todo: remove doubles samples before compiling
 */
class SpamJudgeController extends Controller
{
    /**
     * @Rest\View
     */
    public function newAction(Request $request)
    {
        $em = $this->get('doctrine')->getManager();
        $formFactory = $this->get('form.factory');

        $TextSample = new TextSample();

        $form = $formFactory->create(new TextSampleType(), $TextSample);
        $form->submit($request);

        if ($form->isValid()) {
            $TextSample->setTokenProcessed(0);
            $TextSample->setType((($this->isSpam($TextSample->getSample()))?TextSample::TYPE_SPAM:TextSample::TYPE_HAM));

            $em->persist($TextSample);
            $em->flush();

            $response = new Response();
            $response->setStatusCode(201);

            $response->headers->set(
                'Location',
                $this->generateUrl(
                    'oylex_spam_judge_message_get', array('id' => $TextSample->getId()),
                    true // absolute
                )
            );

            return $response;
        }

        return array('form' => $form);
    }

    /**
     * @Rest\View
     */
    public function getAction($id)
    {
        $em = $this->get('doctrine')->getManager();

        $textSample = $em->getRepository('OylexSpamJudgeBundle:TextSample')->find($id);

        if (!$textSample instanceof TextSample) {
            throw new NotFoundHttpException('Message not found');
        }

        return array(
            'message' => $textSample
        );
    }

    public function indexAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        $this->compileSamples();
        $sample = $request->query->get('sample');
        $type = $request->query->get('type') ?: 0;

        if ($sample) {
            $TextSample = new TextSample();
            $TextSample->setTokenProcessed(0);
            $TextSample->setType($type);
            $TextSample->setIp('');
            $TextSample->setReferrer('');
            $TextSample->setUserAgent('');
            $TextSample->setSample($sample);

            $em->persist($TextSample);
            $em->flush();

            $isSpam = $this->isSpam($sample);

            return new Response((($isSpam)?'Spam':'Ham'));
        } else {
            return new Response('Parameters are: "sample=[string]", "type=[1(spam),2(ham)]"');
        }
    }

    protected function compileSamples()
    {
        $em = $this->getDoctrine()->getManager();

        $textSample = $em->getRepository('OylexSpamJudgeBundle:TextSample')
            ->getUnprocessedSamples();

        if ($textSample) {
            foreach ($textSample as $thisTextSample) {
                $sample = $this->processSample($thisTextSample->getSample());

                $sampleTokens = explode(' ', $sample);

                if ($sampleTokens) {
                    foreach ($sampleTokens as $sampleToken) {
                        $tokenCount = $em->getRepository('OylexSpamJudgeBundle:TokenCount')
                            ->findOneBy(
                                array(
                                    'token' => $sampleToken,
                                    'type'  => $thisTextSample->getType(),
                                )
                            );

                        if ($tokenCount) {
                            $tokenCount->setCount($tokenCount->getCount() + 1);
                        } else {

                            $tokenCount = new TokenCount();
                            $tokenCount->setCount(1);
                            $tokenCount->setType($thisTextSample->getType());
                            $tokenCount->setToken($sampleToken);

                            $em->persist($tokenCount);
                        }

                        $em->flush();
                    }
                }

                $thisTextSample->setTokenProcessed(true);

                $em->flush();
            }
        }
    }

    protected function processSample($sample)
    {
        $blacklist = array();

        $sample = trim($sample);
        $sample = strtolower($sample);
        $sample = str_replace(array('.', ',', '!', '?'), '', $sample);

        $pieces = explode(' ', $sample);

        foreach ($pieces as $key => &$value) {
            trim($value);

            if ($value == '') {
                unset($pieces[$key]);
                continue;
            } else {
                if (isset($blacklist)) {
                    if (in_array($value, $blacklist)) {
                        unset($pieces[$key]);
                        continue;
                    }
                }
            }

            if (mb_strlen($value) <= 3) {
                unset($pieces[$key]);
                continue;
            }
        }

        return trim(implode(' ', $pieces));
    }

    protected function getKnownTokens($sample)
    {
        $em = $this->getDoctrine()->getManager();

        $knownTokens['A'] = array();
        $knownTokens['S'] = array();
        $knownTokens['H'] = array();

        $sample = $this->processSample($sample);

        $sampleTokens = explode(' ', $sample);

        if ($sampleTokens) {
            foreach ($sampleTokens as $sampleToken) {
                $tokenCount = $em->getRepository('OylexSpamJudgeBundle:TokenCount')
                    ->findBy(array('token' => $sampleToken));

                if ($tokenCount) {
                    foreach ($tokenCount as $thisTokenCount) {
                        $knownTokens[(($thisTokenCount->getType() == TokenCount::TYPE_SPAM)?'S':'H')][$thisTokenCount->getToken()] = $thisTokenCount->getCount();
                        $knownTokens['A'][] = $thisTokenCount->getToken();
                    }
                }
            }
        }

        return $knownTokens;
    }

    protected function getTokenSpamicity($tokens)
    {
        $em = $this->getDoctrine()->getManager();

        $textSampleSpam = $em->getRepository('OylexSpamJudgeBundle:TextSample')
            ->findBy(
                array(
                    'type' => TextSample::TYPE_SPAM,
                    'tokenProcessed' => 1,
                )
            );

        $textSampleSpamCount = count($textSampleSpam);

        $textSampleHam = $em->getRepository('OylexSpamJudgeBundle:TextSample')
            ->findBy(
                array(
                    'type' => TextSample::TYPE_HAM,
                    'tokenProcessed' => 1,
                )
            );

        $textSampleHamCount = count($textSampleHam);

        $tokenSpamicity = array();

        if ($tokens['A']) {
            foreach ($tokens['A'] as $thisToken) {
                if (is_array($tokens['S']) && isset($tokens['S'][$thisToken]) && $textSampleSpamCount) {
                    $s = $tokens['S'][$thisToken] / $textSampleSpamCount;
                } else {
                    $s = 0;
                }

                if (is_array($tokens['H']) && isset($tokens['H'][$thisToken]) && $textSampleHamCount) {
                    $h = $tokens['H'][$thisToken] / $textSampleHamCount;
                } else {
                    $h = 0;
                }

                if ($s + $h > 0) {
                    $tokenSpamicity[$thisToken] = $s / ($s + $h);
                } else {
                    $tokenSpamicity[$thisToken] = 0;
                }
            }
        }

        return $tokenSpamicity;
    }

    protected function getSampleSpamProbability($spamicity)
    {
        $products = \array_product($spamicity);

        $oneMinus = array();

        foreach($spamicity as $value)
        {
            $oneMinus[] = 1 - $value;
        }

        $oneMinusProds = \array_product($oneMinus);

        $denominator = ($products + $oneMinusProds);

        $returnProbs = (($denominator > 0)?$products / $denominator:0);

        return round($returnProbs, 2);
    }

    protected function isSpam($message) {
        $knownTokens = $this->getKnownTokens($message);
        $spamicity = $this->getTokenSpamicity($knownTokens);

        $spamProbability = $this->getSampleSpamProbability($spamicity);

        return (($spamProbability > .5)?true:false);
    }
}
