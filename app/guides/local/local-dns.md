### Configure `dnsmasq`
This assumes you use the standard VVV with VirtualBox and the default IP.
Install: dnsmasq was installed above, if you skipped it `brew install dnsmasq`
Setup (https://echo.co/blog/never-touch-your-local-etchosts-file-os-x-again):

```bash
mkdir -pv $(brew --prefix)/etc/
echo 'address=/.dev/192.168.50.4' > $(brew --prefix)/etc/dnsmasq.conf
sudo echo "admin enabled - quickly do sudo tasks"
```

Next type your password in the prompt before continuing.

Now hurry a little, you only have 10 minutes to copy and paste the following commands
```bash
sudo cp -v $(brew --prefix dnsmasq)/homebrew.mxcl.dnsmasq.plist /Library/LaunchDaemons
sudo launchctl load -w /Library/LaunchDaemons/homebrew.mxcl.dnsmasq.plist
sudo mkdir -v /etc/resolver
sudo bash -c 'echo "nameserver 127.0.0.1" > /etc/resolver/dev'
```

##### Test `dnsmasq` is working

Send a ping to `google.dev` and you should see a local IP address in place of a std. Google IP
```
ping -c 1 -t 1 google.dev
```

Expect results like:
```text
PING google.dev (192.168.50.4): 56 data bytes
--- google.dev ping statistics ---
1 packets transmitted, 0 packets received, 100.0% packet loss
```

#### Understanding `dnsmasq`

More info on dnsmasq setup and troubleshooting here:
http://passingcuriosity.com/2013/dnsmasq-dev-osx/

More info on OSX resolver
http://apple.stackexchange.com/questions/74639/do-etc-resolver-files-work-in-mountain-lion-for-dns-resolution

NOTE: nslookup ignores osx dns proxy, do not test with that

#### Optional: Resolve domains using alternative DNS if your ISP's lags

DNS resolves domain names and using your ISP's DNS is kind of like using your ISP's email.

Even business or fiber internet can lean on mediocre DNS, so if you frequently have problems, try a change.

1. Open __System Preferences__ and select __Network__ _[third row]_.
2. Click __Advanced...__ in the bottom-right.
3. Click DNS _[third tab]_
4. Add IPs in left column.

```
Google DNS:
8.8.8.8
8.8.4.4

OpenDNS:
208.67.222.222
208.67.222.220
```