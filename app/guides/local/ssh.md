# Setup SSH

Secure Shell is an encrypted protocol used to connect between two networked computers (on a local network or via the internet). Many web hosts and services like GitHub allow you to authenticate using SSH keys from a trusted machine.

SSH keys only need to be generated once. Instructions are below to copy existing keys or import keys from another computer.

### Check for Current Key(s)
```bash
ls -al ~/.ssh
```

### Copy Current Public Key
```bash
pbcopy < ~/.ssh/id_rsa.pub
```

### Generate New Keys
https://help.github.com/articles/generating-a-new-ssh-key-and-adding-it-to-the-ssh-agent/

### Moving Keys to Another Computer

If you are moving existing keys from a previous computer, you may want to import the keys instead of generating new ones.

1. Copy the existing keys into the new computer's `~/.ssh` folder.
2. After copying the keys over, the file permissions will be too open and in some cases won't be accepted when trying to connect to servers. To set the correct permissions, run `chmod 600 ~/.ssh/id_rsa` and `chmod 600 ~/.ssh/id_rsa.pub`. It should also be noted that the .ssh folder itself should only be writeable by you (`chmod 700 ~/.ssh`).
3. Run `ssh-add -k ~/.ssh/id_rsa`.


### Understanding SSH Key Pairs

SSH keys are generated at user prompt -- they don't come installed on computers.

Key pairs are like deadbolts & keys. Your **private key** is akin to _your_ house key, it only works with _your_ house's deadbolt lock. While the **public key** is more like the deadbolt lock on your house. The pair, the key and the deadbolt, together allows you secure access into your home.

In the case of SSH key pairs, you keep the private key to yourself, never share this with anyone else. You share your public key with, well anyone who needs to grant you access to there servers. When they add your public key (deadbolt) to their servers, it’s like giving you your own backdoor into the servers that only your private key works with. (Seen the key-maker in the Matrix movie? Kind of like that).

### Configure SSH Forwarding Agent

https://developer.github.com/guides/using-ssh-agent-forwarding/#setting-up-ssh-agent-forwarding

### Example for connecting to Pagely Hosting
Open terminal editor nano for .ssh config file:
```bash
nano ~/.ssh/config
```

Then copy and paste the following into the editor.
```bash
Host *.pagelydev.com
     ForwardAgent yes
Host *.pagelyhosting.com
     ForwardAgent yes
```
Lastly save and close the file with `Ctrl+X` then `Enter` and one more `Enter`.

### Understanding forwarding with the ssh agent

Basically, when you are using SSH or SSH tunnels, you need to "grant access" to your private key. This allows us to pass our SSH key to the remote server we are connecting to, and it can now use your key to "forward" your requests to additional servers that it may talk to. You don't want to allow this for "just every server you connect to". That would be dangerous since you need to trust the connecting server not to abuse the use or sharing of your private key. Remember your private key, the one without .pub at the end, is like a password. Do not share your private key with anyone, and we highly recommend you do not "sync it to a cloud drive service". Certainly never store it in a Github repository, regardless of the repo is private or public.