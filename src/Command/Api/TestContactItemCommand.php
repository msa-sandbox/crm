<?php

declare(strict_types=1);

namespace App\Command\Api;

use App\Api\V1\Dto\Request\Contact\GetContactItemQueryDto;
use App\Api\V1\Handler\Contact\GetContactHandler;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[AsCommand(
    name: 'api:test:contacts:item',
    description: 'Test command to get one contact (GET /contacts/{id})',
)]
class TestContactItemCommand extends Command
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
            ->addArgument('id', InputArgument::REQUIRED, 'ID contact')
            ->addOption('with', null, InputOption::VALUE_OPTIONAL, 'Method…')
            ->addOption('include-deleted', null, InputOption::VALUE_NONE, 'Add deleted');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Authorize user
        $this->authenticateCli($this->tokenStorage);

        $id = (int) $input->getArgument('id');
        $with = $input->getOption('with');
        $includeDeleted = $input->getOption('include-deleted');

        // Make the same DTO as in controller
        $dto = new GetContactItemQueryDto(
            with: $with,
            includeDeleted: $includeDeleted,
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

        $result = $this->handler->getOneById($id, $dto);

        // Show result
        dump($result);

        $io->success('Done');

        return Command::SUCCESS;
    }
}
