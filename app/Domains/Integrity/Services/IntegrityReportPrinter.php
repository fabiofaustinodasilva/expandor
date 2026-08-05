<?php

namespace App\Domains\Integrity\Services;

use App\Domains\Integrity\DTOs\IntegrityReport;
use Illuminate\Console\Command;

class IntegrityReportPrinter
{
    public function print(Command $command, IntegrityReport $report, bool $dryRun): void
    {
        $command->newLine();
        $command->info('Integrity Report'.($dryRun ? ' (DRY-RUN — nenhuma alteração)' : ''));
        $command->line(str_repeat('=', 48));

        $command->newLine();
        $command->comment('Duplicated Emails');
        if ($report->duplicatedEmails === []) {
            $command->line('  (nenhum)');
        } else {
            foreach ($report->duplicatedEmails as $group) {
                $command->line('  '.$group['email']);
                foreach ($group['companies'] as $company) {
                    $command->line('    Empresa '.$company['id'].' — '.$company['name']);
                }
            }
        }

        $command->newLine();
        $command->comment('Duplicated Documents');
        if ($report->duplicatedDocuments === []) {
            $command->line('  (nenhum)');
        } else {
            foreach ($report->duplicatedDocuments as $group) {
                $command->line('  '.$group['document']);
                foreach ($group['companies'] as $company) {
                    $command->line('    Empresa '.$company['id'].' — '.$company['name']);
                }
            }
        }

        $command->newLine();
        $command->comment('Soft Deleted Companies');
        if ($report->softDeletedCompanies === []) {
            $command->line('  (nenhuma)');
        } else {
            foreach ($report->softDeletedCompanies as $company) {
                $command->line('  Empresa '.$company['id'].' — '.$company['name'].' ('.$company['status'].')');
            }
        }

        $command->newLine();
        $command->comment('Cancelled Companies');
        if ($report->cancelledCompanies === []) {
            $command->line('  (nenhuma)');
        } else {
            foreach ($report->cancelledCompanies as $company) {
                $command->line('  Empresa '.$company['id'].' — '.$company['name']);
            }
        }

        $command->newLine();
        $command->comment('Orphan Records');
        foreach ($report->orphanCounts as $label => $count) {
            $command->line(sprintf('  %-32s %d', $label, $count));
        }

        $command->newLine();
        $command->comment('UNIQUE Indexes');
        $command->line('  users.email ............... '.($report->uniqueEmailIndexPresent ? 'OK' : 'MISSING'));
        $command->line('  companies.document ........ '.($report->uniqueDocumentIndexPresent ? 'OK' : 'MISSING'));

        $command->newLine();
        $command->comment('Recommended Purges');
        if ($report->companiesRecommendedForPurge === []) {
            $command->line('  (nenhuma)');
        } else {
            foreach ($report->companiesRecommendedForPurge as $item) {
                $command->line('  Empresa '.$item['id'].' — '.$item['name']);
                foreach ($item['reasons'] as $reason) {
                    $command->line('    · '.$reason);
                }
            }
        }

        if ($report->notes !== []) {
            $command->newLine();
            $command->comment('Notes');
            foreach ($report->notes as $note) {
                $command->line('  - '.$note);
            }
        }

        $command->newLine();
        $command->line($report->hasIssues() ? 'Status: ISSUES FOUND' : 'Status: CLEAN');
    }
}
