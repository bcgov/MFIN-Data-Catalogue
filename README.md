Base Build - Drupal side
==========

Brings the deployment introduced by Openplus to the MFIN DataCatalogue project, into any project.

This is intended to be used for Drupal projects in the Ministry of Finance, but could easily be used for other applications.

---------
Note that there are two branches in this repo, containing the two halves of the basebuild:

  * drupal
  * gitops

The 'drupal' branch is discussed here.

See the README in the 'gitops' branch for a discussion of it.

Project-installation guide
--------------------------
Be sure to read the wiki for this project, as it contains the main guidance for how to set up a new project.

  * https://github.com/bcgov-c/fin-basebuild/wiki


How to use the 'drupal' branch
------------------------------

Install this project in its own branch, with its own remote, and only fetch the 'drupal' branch from it.

```
    # Add the new remote
    git remote add basebuild  https://github.com/bcgov-c/fin-basebuild.git

    # Fetch only the drupal branch from it
    git fetch basebuild drupal

    # Create a local branch that tracks the remote one.
    git branch --track bb-drupal basebuild/drupal
```

Make sure you're on your own project's main branch, and merge in the Base Build's latest code.

    git checkout master
    git merge basebuild/drupal --allow-unrelated-histories -m "Add basebuild code to our Drupal project."
    # (And resolve any merge-conflicts.)

Search for instances of the text "newbb" (aka "new basebuild"). They are all things you'll need to set for your project:

* `newbbproj` should be replaced with your project-name.
* `newbblicenseplate` must be replaced with the 6-character "license plate" of your openshift project.
* `NEWBB` is used to flag other items that need your attention.

You are ready to go!

In the future
-------------
When there are **new changes in the basebuild** branch/repo:
 * `git fetch` them, and merge the gitops branch into your code again.
 * MANUALLY inspect the updates. If there are parts of it you don't want, then add the `--no-commit` option to your `git merge` command, and then edit out the changes you don't want before doing a `git commit` to complete the merge.

If there are **changes in your project** that should be shared with the basebuild:

* `git checkout gitops`
* _Cherry-pick_ commits onto this branch.
* **Never `git merge` from your project into the basebuild!**
* `git push basebuild` to share them back to the basebuild remote.

-----

NEWBB: Once you have your project set up, you can delete some of the above text, and convert this README to be the README for your own project, not for the basebuild.
