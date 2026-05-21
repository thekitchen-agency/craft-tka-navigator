<?php

namespace thekitchenagency\crafttkanavigation\controllers;

use Craft;
use craft\web\Controller;
use thekitchenagency\crafttkanavigation\elements\Navigation;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class NavigationController extends Controller
{
    public function actionIndex(): Response
    {
        $navigations = Navigation::find()->all();

        return $this->renderTemplate('tka-navigation/index', [
            'navigations' => $navigations,
        ]);
    }

    public function actionEdit(int $elementId = null): Response
    {
        $siteId = Craft::$app->getRequest()->getParam('siteId');
        if (!$siteId) {
            $siteId = Craft::$app->getSites()->getCurrentSite()->id;
        }

        if ($elementId) {
            $navigation = Navigation::find()->id($elementId)->siteId($siteId)->one();
            if (!$navigation) {
                // If it exists in another site but not this site yet, let's load it on the default site
                $navigation = Navigation::find()->id($elementId)->anyStatus()->one();
                if (!$navigation) {
                    throw new NotFoundHttpException('Navigation not found.');
                }
                $navigation->siteId = $siteId;
            }
        } else {
            $navigation = new Navigation();
            $navigation->siteId = $siteId;
        }

        // Extract entry names for the frontend editor
        $entryNames = [];
        if ($navigation->nodes) {
            $entryIds = self::extractEntryIds($navigation->nodes);
            if (!empty($entryIds)) {
                $entries = \craft\elements\Entry::find()->id($entryIds)->siteId($siteId)->all();
                foreach ($entries as $entry) {
                    $entryNames[$entry->id] = $entry->title;
                }
            }
        }

        return $this->renderTemplate('tka-navigation/edit', [
            'navigation' => $navigation,
            'siteId' => $siteId,
            'entryNames' => $entryNames,
        ]);
    }

    private static function extractEntryIds(array $nodes): array
    {
        $ids = [];
        foreach ($nodes as $node) {
            if (!empty($node['entryId'])) {
                $ids[] = (int)$node['entryId'];
            }
            if (!empty($node['children'])) {
                $ids = array_merge($ids, self::extractEntryIds($node['children']));
            }
        }
        return array_unique($ids);
    }

    public function actionSave(): ?Response
    {
        $this->requirePostRequest();
        $request = Craft::$app->getRequest();

        $elementId = $request->getBodyParam('elementId');
        $siteId = $request->getBodyParam('siteId') ?: Craft::$app->getSites()->getCurrentSite()->id;

        if ($elementId) {
            $navigation = Navigation::find()->id($elementId)->siteId($siteId)->one();
            if (!$navigation) {
                $navigation = Navigation::find()->id($elementId)->anyStatus()->one();
                if (!$navigation) {
                    throw new NotFoundHttpException('Navigation not found.');
                }
                $navigation->siteId = $siteId;
            }
        } else {
            $navigation = new Navigation();
        }

        $navigation->siteId = $siteId;
        $navigation->title = $request->getBodyParam('title');
        $navigation->handle = $request->getBodyParam('handle');

        $nodesJson = $request->getBodyParam('nodes');
        $navigation->nodes = json_decode($nodesJson, true) ?: [];

        if (!Craft::$app->getElements()->saveElement($navigation)) {
            Craft::$app->getSession()->setError(Craft::t('tka-navigation', 'Couldn’t save navigation.'));
            return $this->renderTemplate('tka-navigation/edit', [
                'navigation' => $navigation,
                'siteId' => $siteId,
            ]);
        }

        Craft::$app->getSession()->setNotice(Craft::t('tka-navigation', 'Navigation saved.'));
        return $this->redirectToPostedUrl($navigation);
    }

    public function actionDelete(): Response
    {
        $this->requirePostRequest();
        $elementId = Craft::$app->getRequest()->getRequiredBodyParam('id');
        $navigation = Navigation::find()->id($elementId)->one();

        if ($navigation && Craft::$app->getElements()->deleteElement($navigation)) {
            Craft::$app->getSession()->setNotice(Craft::t('tka-navigation', 'Navigation deleted.'));
        } else {
            Craft::$app->getSession()->setError(Craft::t('tka-navigation', 'Couldn’t delete navigation.'));
        }

        return $this->redirectToPostedUrl();
    }
}
