# Making new version release

How to publish new version.

## Prepare new version code

* Update version in `tg-instantview/tg-instantview.php`
* Update release notes in `tg-instantview/readme.txt`
* Update current version in `tg-instantview/readme.txt`

## Upload code changes

Define a new tag:

```bash
export VERSION=1.5
```

Prepare git update and tag:

```bash
git add .
git commit -am "Version ${VERSION}"
git push

git tag v$VERSION
git push origin v$VERSION

./mk-release.sh
```

Publish new Release from git tag.

Publish new version into WP svn:

```bash
cd tmp/tg-instantview-svn
svn revert --recursive .
svn up
cp -rva ../../tg-instantview/* trunk/
svn cp --parents trunk/* tags/$VERSION
svn add --force .
svn ci -m "Version $VERSION"
```

Done: new version published.
