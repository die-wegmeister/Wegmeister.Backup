<?php

namespace Wegmeister\Backup\Command;

/*
 * This file is part of the Wegmeister.Backup package.
 */

use Neos\ContentRepository\Domain\Service\ContextFactoryInterface;
use Neos\ContentRepository\Domain\Service\ImportExport\NodeExportService;
use Neos\ContentRepository\Domain\Service\ImportExport\NodeImportService;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Cli\CommandController;
use Neos\Neos\Domain\Repository\SiteRepository;
use Neos\Neos\Domain\Service\ContentContext;
use Neos\Utility\Files;

#[Flow\Scope("singleton")]
class ContentCommandController extends CommandController
{
    #[Flow\Inject]
    protected ContextFactoryInterface $contextFactory;

    #[Flow\Inject]
    protected SiteRepository $siteRepository;

    #[Flow\Inject]
    protected NodeExportService $nodeExportService;

    #[Flow\Inject]
    protected NodeImportService $nodeImportService;

    /**
     * Export node tree from given source node to XML
     *
     * @param string $siteNodeName Site node name
     * @param string $sourceNodeIdentifier Node identifier of starting point node
     * @param string $filename Filename to write XML to. Resources directory will be created at same path of Filename
     * @param boolean $tidy
     * @param ?string $nodeTypeFilter Filter the node type of the nodes, allows complex expressions (e.g. "Neos.Neos:Page", "!Neos.Neos:Page,Neos.Neos:Text")
     * @param ?string $workspace The workspace name to export from. If not set, the live workspace will be used.
     * @throws \Neos\Flow\Mvc\Exception\StopActionException
     */
    public function exportCommand(string $siteNodeName, string $sourceNodeIdentifier, string $filename, bool $tidy = true, ?string $nodeTypeFilter = null, ?string $workspace = null)
    {
        $site = $this->siteRepository->findOneByNodeName($siteNodeName);
        if ($site === null) {
            $this->outputLine('<error>No site with node name "%s" found</error>', [$siteNodeName]);
            $this->quit(1);
        }

        /** @var ContentContext $contentContext */
        $contentContext = $this->contextFactory->create([
            'workspaceName' => $workspace ?? 'live',
            'currentSite' => $site,
            'invisibleContentShown' => true,
            'inaccessibleContentShown' => true
        ]);

        $sourceNode = $contentContext->getNodeByIdentifier($sourceNodeIdentifier);
        if ($sourceNode === null) {
            $this->outputLine('<error>No node with identifier "%s" found</error>', [$sourceNodeIdentifier]);
            $this->quit(1);
        }

        $resourcesPath = Files::concatenatePaths([dirname($filename), 'Resources']);
        Files::createDirectoryRecursively($resourcesPath);


        $xmlWriter = new \XMLWriter();
        $xmlWriter->openUri($filename);
        $xmlWriter->setIndent($tidy);
        $xmlWriter->startDocument('1.0', 'UTF-8');

        $this->nodeExportService->export($sourceNode->getPath(), $contentContext->getWorkspaceName(), $xmlWriter, $tidy, true, $resourcesPath, $nodeTypeFilter);
        $xmlWriter->flush();
        $this->outputLine('Export finished');
    }

    /**
     * Import node tree from given XML file into target node
     *
     * @param string $siteNodeName Site node name
     * @param string $targetNodeIdentifier Target node identifier of the parent under which the node tree will be imported
     * @param string $filename Filename to read XML from
     * @throws \Neos\Flow\Mvc\Exception\StopActionException
     */
    public function importCommand(string $siteNodeName, string $targetNodeIdentifier, string $filename)
    {
        if (!is_file($filename)) {
            $this->outputLine('<error>File "%s" not found</error>', [$filename]);
            $this->quit(1);
        }

        $site = $this->siteRepository->findOneByNodeName($siteNodeName);
        if ($site === null) {
            $this->outputLine('<error>No site with node name "%s" found</error>', [$siteNodeName]);
            $this->quit(1);
        }

        /** @var ContentContext $contentContext */
        $contentContext = $this->contextFactory->create([
            'currentSite' => $site,
            'invisibleContentShown' => true,
            'inaccessibleContentShown' => true
        ]);

        if ($targetNodeIdentifier === '') {
            $targetNode = $contentContext->getCurrentSiteNode();
        } else {
            $targetNode = $contentContext->getNodeByIdentifier($targetNodeIdentifier);
        }

        if ($targetNode === null) {
            $this->outputLine('<error>No node with identifier "%s" found</error>', [$targetNodeIdentifier]);
            $this->quit(1);
        }

        $xmlReader = new \XMLReader();
        if ($xmlReader->open($filename, null, LIBXML_PARSEHUGE) === false) {
            $this->outputLine('<error>Error: XMLReader could not open "%s"</error>', [$filename]);
            $this->quit(1);
        }
        $this->nodeImportService->import($xmlReader, $targetNode->getPath(), dirname($filename) . '/Resources');
        $this->outputLine('Import finished');
    }
}
