### Quickstart

###### Download and Install Homebrew Package Manager

```bash
ruby -e "$(curl -fsSL https://raw.githubusercontent.com/Homebrew/install/master/install)"
```

#### Why Brew?

Brew helps developers manages software and tools be time-consuming and challenging to install and configure. Brew, in conjunction with Cask, can be a near-one-stop shop for installing _any_ software.

It's rad.

### Recommended Developer Packages
```bash
brew tap caskroom/cask && \
brew install grc git svn node imagemagick pkg-config hub dnsmasq && \
git config --global credential.helper osxkeychain
brew cask install virtualbox vagrant vagrant-manager
brew cask install iterm2 sequel-pro postman visual-studio-code
```
### Recommended for Productivity
```bash
brew cask install phpstorm slack lastpass
brew cask install alfred spectacle flux dash imageoptim clipy
brew cask install filezilla google-chrome spotify snagit
```
### Recommended for OS X Finder Quick Look Previews
```
brew cask install qlcolorcode qlstephen qlmarkdown quicklook-json qlprettypatch quicklook-csv betterzipql qlimagesize webpquicklook suspicious-package quicklookase qlvideo
```
Originally from https://github.com/sindresorhus/quick-look-plugins