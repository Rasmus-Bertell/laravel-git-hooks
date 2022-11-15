<?php

namespace Igorsgm\GitHooks\Traits;

use Closure;
use Igorsgm\GitHooks\Exceptions\HookFailException;
use Igorsgm\GitHooks\Facades\GitHooks;
use Igorsgm\GitHooks\Git\ChangedFiles;
use Igorsgm\GitHooks\Git\CommitMessage;

trait WithCommitMessage
{
    use WithPipeline;

    /**
     * Execute the console command.
     */
    public function handle()
    {
        try {
            $message = GitHooks::getCommitMessageContentFromFile(
                $this->getMessagePath()
            );

            $this->sendMessageThroughHooks(
                new CommitMessage(
                    $message,
                    new ChangedFiles(
                        GitHooks::getListOfChangedFiles()
                    )
                )
            );
        } catch (HookFailException $e) {
            return 1;
        }
    }

    /**
     * Get the git message path (By default .git/COMMIT_MESSAGE)
     *
     * @return string
     */
    private function getMessagePath(): string
    {
        $file = $this->argument('file');

        return base_path($file);
    }

    /**
     * Send the given message from .git/COMMIT_MESSAGE through the pipes
     *
     * @param  CommitMessage  $message
     */
    protected function sendMessageThroughHooks(CommitMessage $message): void
    {
        $this->makePipeline()
            ->send($message)
            ->then($this->storeMessage());
    }

    /**
     * Store prepared message
     *
     * @return Closure
     */
    protected function storeMessage(): Closure
    {
        return function (CommitMessage $message) {
            GitHooks::updateCommitMessageContentInFile(
                $this->getMessagePath(),
                (string) $message
            );
        };
    }
}
