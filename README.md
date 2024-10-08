Base Build
==========

Brings the deployment introduced by Openplus to the MFIN DataCatalogue project, into any project.

This is intended to be used for Drupal projects in the Ministry of Finance, but could easily be used for other applications.

How to use
----------

Install this project in its own branch, with its own remote.

    git remote add base-build https://github.com/danhgov/basebuild.git
    git fetch base-build

Make sure you're on your `master` branch, and merge in the Base Build's latest code.

    git checkout master
    git merge base-build/master --allow-unrelated-histories -m "Add base-build code to our project."
    # (And resolve any merge-conflicts.)

Search for instances of the text "newbb" (aka "new base build"). They are all things you'll need to set for your project:

* `newbbproj` should be replaced with your project-name.
* `newbblicenseplate` must be replaced with the 6-character "license plate" of your openshift project.
* `NEWBB` is used to flag other items that need your attention.

You are ready to go!

---

In the future, you can pull updates that may have been committed on the base-build into your project.

    # Fetch the updates
    git fetch base-build

    # MANUALLY inspect the updates prior to continuing with the merge.

    # THEN, continue with the merge...
    git merge base-build/master -m "Merge updates from base-build project into ours."


---------

(NEWBB - After getting set up you should replace this README file with a description of YOUR project.)

