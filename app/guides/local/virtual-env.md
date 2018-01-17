## Setting Up WordPress Development

Fully setting up local WordPress development of a custom site isn't that complicated, but its complex enough that we don't want to try do a comprehensive explanation in readme file.

### VVV Pre Reqs:
```
vagrant plugin install vagrant-hostsupdater
vagrant plugin install vagrant-triggers
```

### Quick setup of VVV:
```
mkdir -p ~/Sites; cd ~/Sites && git clone https://github.com/Varying-Vagrant-Vagrants/VVV.git ; cd VVV && vagrant up ;
```

### But I want a custom local WordPress instance
Enter VVV2 YML configs. Allowing you to tell VVV that you want more local instances, and how you want those instances configured. Read more in the [official VVV2 config docs](https://varyingvagrantvagrants.org/docs/en-US/vvv-config/). Would you like to see a video to follow along? We got you covered there too!

[![IMAGE ALT TEXT](http://img.youtube.com/vi/6XC1er-2pmM/0.jpg)](https://www.youtube.com/watch?v=6XC1er-2pmM "Video Title")

First we need to create a custom config file that VVV2 is expecting:

```bash
cp VVV/vvv-config.yml VVV/vvv-custom.yml
```

Next we need to add a custom domain to the configuration file. 

*Please note: YML files are space sensetive, if its not working, its likely an issue with indentiation/spacing/nesting.*

Paste in the following just above the utilities section of vvv-custom.yml:
```yaml
  mylocalsite.dev:
    repo: https://github.com/Varying-Vagrant-Vagrants/custom-site-template.git
    hosts:
      - mylocalsite.dev
```

Last we need to ask VVV to build our new configuration. We can completely rebuild the entire Vagrant, or, whats faster is to ask VVV2 to just build our new site within the Vagrant. We are going to show here the more efficient way of building just our 1 new site from the config.

```
vagrant up ; vagrant provision --provision-with site-mylocalsite.dev'
```

### But I want VVV2 to mirror my production site
You mean you don't want to manually configure every site locally every time you set them up? Well, you are in luck. VVV2 allows us to do that by defining the entire environment using a Github repo. Please note that this is advanced, and requires some previous knowledge of DevOps, bash shell scripts, and VVV provisioning itself. 

We have prepared a boilerplate repo for you to download / fork / clone as you would like. You can check out the readme file, and the provision/vvv-init.sh file for notes about how to go about configuring your own private staging server, right inside VVV.

https://github.com/WordPress-Phoenix/vvv2-provision-boilerplate

## Connecting Sequel Pro to VVV

1. Open Sequel Pro
2. Click +Add New Connection
3. Click SSH tab option and fill out the fields below:
```
MySQL Host: 127.0.0.1
User: 		wp
Pass: 		wp
Database: 	vvv.dev
Port: 		3306
SSH Host: 	vvv.dev
SSH User: 	vagrant
SSH Pass: 	vagrant
SSH Port: 	(none)
Unchecked SSL box
```