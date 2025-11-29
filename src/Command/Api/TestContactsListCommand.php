<?php

declare(strict_types=1);

namespace App\Command\Api;

use App\Api\V1\Dto\Request\Contact\GetContactQueryDto;
use App\Api\V1\Handler\Contact\GetContactHandler;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[AsCommand(
    name: 'api:test:contacts:list',
    description: 'Test command to get all contacts (GET /contacts)',
)]
class TestContactsListCommand extends Command
{
    use CliAuthTrait;

    public function __construct(
        private readonly GetContactHandler $handler,
        private readonly ValidatorInterface $validator,
        private readonly TokenStorageInterface $tokenStorage,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('after-id', null, InputOption::VALUE_OPTIONAL, 'afterId (pagination)')
            ->addOption('limit', null, InputOption::VALUE_OPTIONAL, 'limit (1-100)', 20)
            ->addOption('include-deleted', null, InputOption::VALUE_NONE, 'Add deleted')
            ->addOption('search', null, InputOption::VALUE_OPTIONAL, 'Custom search')
            ->addOption('with', null, InputOption::VALUE_OPTIONAL, 'Embedded entities, e.g. "leads"');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Authorize user
        $this->authenticateCli($this->tokenStorage);

        $afterId        = $input->getOption('after-id');
        $limit          = $input->getOption('limit');
        $includeDeleted = $input->getOption('include-deleted');
        $search         = $input->getOption('search');
        $with           = $input->getOption('with');

        // Make the same DTO as in controller
        $dto = new GetContactQueryDto(
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
                $io->writeln(sprintf(
                    '- %s: %s',
                    $violation->getPropertyPath(),
                    $violation->getMessage()
                ));
            }

            return Command::FAILURE;
        }

        $result = $this->handler->getList($dto);

        // Show result
        dump($result);

        $io->success('Done');

        return Command::SUCCESS;
    }
}
