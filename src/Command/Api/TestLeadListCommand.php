<?php

declare(strict_types=1);

namespace App\Command\Api;

use App\Api\V1\Dto\Request\Lead\GetLeadQueryDto;
use App\Api\V1\Handler\Lead\GetLeadHandler;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[AsCommand(
    name: 'api:test:leads:list',
    description: 'Test command to get all leads (GET /leads)',
)]
class TestLeadListCommand extends Command
{
    use CliAuthTrait;

    public function __construct(
        private readonly GetLeadHandler $handler,
        private readonly ValidatorInterface $validator,
        private readonly TokenStorageInterface $tokenStorage,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('after-id', null, InputOption::VALUE_OPTIONAL, 'Pagination start ID')
            ->addOption('limit', null, InputOption::VALUE_OPTIONAL, 'Limit per page (default 20, max 100)', 20)
            ->addOption('include-deleted', null, InputOption::VALUE_NONE, 'Include soft-deleted leads')
            ->addOption('search', null, InputOption::VALUE_OPTIONAL, 'Full-text search')
            ->addOption('with', null, InputOption::VALUE_OPTIONAL, 'Related entities, e.g. "contacts"');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // CLI authentication
        $this->authenticateCli($this->tokenStorage);

        // Collect parameters
        $afterId        = $input->getOption('after-id');
        $limit          = $input->getOption('limit');
        $includeDeleted = $input->getOption('include-deleted');
        $search         = $input->getOption('search');
        $with           = $input->getOption('with');

        // Build DTO
        $dto = new GetLeadQueryDto(
            afterId: $afterId,
            limit: $limit,
            includeDeleted: $includeDeleted,
            search: $search,
            with: $with,
        );

        $violations = $this->validator->validate($dto);
        if (count($violations) > 0) {
            $io->error('DTO validation failed');
            foreach ($violations as $violation) {
                $io->writeln(sprintf('- %s: %s', $violation->getPropertyPath(), $violation->getMessage()));
            }

            return Command::FAILURE;
        }

        // Call handler
        $result = $this->handler->getList($dto);

        dump($result);

        $io->success('Done');

        return Command::SUCCESS;
    }
}
