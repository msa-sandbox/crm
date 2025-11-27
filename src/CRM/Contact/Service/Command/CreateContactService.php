<?php

declare(strict_types=1);

namespace App\CRM\Contact\Service\Command;

use App\CRM\Contact\Contract\ContactRepositoryInterface;
use App\CRM\Contact\Contract\CreateContactInterface;
use App\CRM\Contact\Entity\Contact;
use App\Service\TransactionManager;
use Throwable;

readonly class CreateContactService implements CreateContactInterface
{
    public function __construct(
        private ContactRepositoryInterface $contactRepository,
        private TransactionManager $transactionManager,
    ) {
    }

    /**
     * @param Contact[] $data
     * @param int $accountId
     *
     * @return Contact[]
     *
     * @throws Throwable
     */
    public function createContacts(array $data, int $accountId): array
    {
        return $this->transactionManager->execute(function () use ($data, $accountId) {
            // Collect all emails (without null and empty string)
            $emails = array_values(array_unique(
                array_filter(
                    array_map(
                        function (Contact $c) {
                            $email = $c->getEmail();

                            return $email ? strtolower($email) : null; // As a filter to avoid a case "was null and now empty string"
                        },
                        $data
                    )
                )
            ));

            // Find existing contacts by emails
            $existing = $this->contactRepository->findExistingByEmailsAndAccount($emails, $accountId);

            // Create a map: email => Contact
            $existingMap = [];
            foreach ($existing as $c) {
                $existingMap[strtolower($c->getEmail())] = $c;
            }

            $result = [];
            foreach ($data as $contact) {
                $email = strtolower((string) $contact->getEmail());

                if ($email && isset($existingMap[$email])) {
                    // If already exists - update it
                    $existing = $existingMap[$email];
                    $existing->updateFrom($contact);
                    $result[] = $existing;
                } else {
                    // New contact
                    $this->contactRepository->add($contact);
                    $result[] = $contact;
                }
            }

            $this->contactRepository->flush();

            return $result;
        });
    }
}
