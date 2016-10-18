<?php
namespace system\controller;

class PatchNotes
{
    function hasNewPatchNotes(\users\model\User $user)
    {
        $patchNoteTime = filemtime("documents/changelog.txt");
        $patchNoteHash = md5_file("documents/changelog.txt");

        $cache = \User::getUSER()->getConfig("patchnotes");
        if ($cache)
        {
            $cache = json_decode($cache);
            if (isset($cache->time) && isset($cache->hash))
            {
                if ($cache->time < $patchNoteTime) {
                    if ($cache->hash != $patchNoteHash)
                        return true;
                }
            }
        }

        $this->registerPatchNotes($user);
        return false;
    }

    function registerPatchNotes(\users\model\User $user)
    {
        $patchNoteTime = filemtime("documents/changelog.txt");
        $patchNoteHash = md5_file("documents/changelog.txt");

        $cache = new \stdClass();
        $cache->time = $patchNoteTime;
        $cache->hash = $patchNoteHash;
        $user->setConfig("patchnotes", $cache);
    }
}