# Setup Terminal
Setting up your terminal profile is very important for productivity. We recommend the following enhancements. Being by opening your bash profile configuration file.

### Setup `.bash_profile`
```bash
sudo nano ~/.bash_profile
```
Then copy the following and past it into the terminal text editor we just used to open the file.
```bash
alias ls="ls -GHf"
alias ll='ls -lartG'
alias xdebug_on='vagrant ssh -c "xdebug_on" | grep php'
alias xdebug_off='vagrant ssh -c "xdebug_off" | grep php'
alias hosts='sudo nano /private/etc/hosts'
alias flushdns='sudo dscacheutil -flushcache'
alias vup="vagrant up --provision"
export PATH="/usr/local/sbin:$PATH"
alias showFiles='defaults write com.apple.finder AppleShowAllFiles YES; killall Finder/System/Library/CoreServices/Finder.app'
alias hideFiles='defaults write com.apple.finder AppleShowAllFiles NO; killall Finder /System/Library/CoreServices/Finder.app'
alias pruneknownhosts="sed -i -e s/*.*//g ~/.ssh/known_hosts"
alias prunedevhosts="sed -i -e s/.*\.dev.*//g ~/.ssh/known_hosts"

# LOCAL GIT HELPERS
alias updaterepos='ls | xargs -P10 -I{} git -C {} pull'
alias prunerepos='ls | xargs -P10 -I{} git -C {} remote prune origin'
alias checkoutdevelopall='ls | xargs -P10 -I{} git -C {} checkout develop'

# REST HELPER
httpHeaders () { /usr/bin/curl -I -L $@ ; }

# docker functions
alias dke="docker exec -i -t"
alias dkps="docker ps -a"
alias dkls="docker images"
dkRun () { docker run -dit $@ ; }
dkBuild () { docker build . -t $@ ; }
dkClean () { [[ $(docker ps -a -q -f status=exited) ]] && docker rm -v $(docker ps -a -q -f status=exited) ; }
dkCleanImages () { docker rmi $(docker images | grep "^<none>" | awk '{print $3}') ; }
```

Lastly save and close the file with `Ctrl+X` then `Enter` and one more `Enter`.

### Setup `.inputrc`
Input RC controls what happens as you type in terminal. We are going to setup a feature that allows terminal to use "per command history". When in terminal you can hit the up and down arrows to cycle through previously used commands. With this extra configuration file, it will allow you to track "per command" history. So if you typed "git push" then "cd www", you may want to only search your history for "git" commands. To do so, simply type "git" and then press up, it will only search your history for git commands and find "git push" as desired.

Open the .inputrc file from terminals nano editor:
```
nano ~/.inputrc
```

Then copy and paste the following into the editor.
```
"\e[B": history-search-forward
"\e[A": history-search-backward
set completion-ignore-case On
```

Lastly save and close the file with `Ctrl+X` then `Enter` and one more `Enter`.

### Applying New Settings To Terminal

Updates to the `.bash_profile` don't immediately reflect in your current editor. Open a new tab, restart terminal or source your profile for the current session.
```
source ~/.bash_profile
```