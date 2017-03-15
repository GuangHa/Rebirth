<?php
namespace Ttree\Rebirth\Command;

/*
 * This file is part of the Ttree.Rebirth package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 */

use Ttree\Rebirth\Service\OrphanNodeService;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Cli\CommandController;
use TYPO3\Neos\Controller\Exception\NodeNotFoundException;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;

class RebirthCommandController extends CommandController
{
    /**
     * @var OrphanNodeService
     * @Flow\Inject
     */
    protected $orphanNodeService;

    /**
     * List orphan documents
     *
     * @param string $workspace
     * @param string $type
     */
    public function listCommand($workspace = 'live', $type = 'TYPO3.Neos:Document')
    {
        $this->command($workspace, $type, false);
    }

    /**
     * Restore orphan document
     *
     * @param string $workspace
     * @param string $type
     * @param string $target
     */
    public function restoreCommand($workspace = 'live', $type = 'TYPO3.Neos:Document', $target = null)
    {
        $this->command($workspace, $type, true, $target);
    }

    /**
     * @param string $workspace
     * @param string $type
     * @param bool $restore
     * @param string $targetIdentifier
     */
    protected function command($workspace, $type, $restore = false, $targetIdentifier = null)
    {
        $nodes = $this->orphanNodeService->listByWorkspace($workspace, $type);
        $nodes->map(function (NodeInterface $node) use ($restore, $targetIdentifier) {
            $this->output->outputLine('%s <comment>%s</comment> (%s) in <b>%s</b>', [$node->getIdentifier(), $node->getLabel(), $node->getNodeType(), $node->getPath()]);
            if ($restore) {
                try {
                    $target = $this->orphanNodeService->target($node, $targetIdentifier);
                    $this->outputLine('  <info>Restore to %s (%s)</info>', [$node->getPath(), $node->getIdentifier()]);
                    $this->orphanNodeService->restore($node, $target);
                    $this->outputLine('  <info>Done, check your node at "%s"</info>', [$node->getPath()]);
                } catch (NodeNotFoundException $exception) {
                    $this->outputLine('  <error>Missing restoration target for the current node</error>');
                    return;
                }
            }
        });

        if ($nodes->count()) {
            $this->outputLine('<b>Processed nodes:</b> %d', [$nodes->count()]);
        } else {
            $this->outputLine('<b>No orphaned document nodes</b>');
        }
    }
}
