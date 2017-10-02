# LAMP Stack with CentOS 7, Vagrant and Ansible

Author: Lander Van den Bulcke

Disclaimer: This was made as a school assignment. Therefore, no guarantees in terms of quality/functionality. ;)

## Software required

### Local environment

- Vagrant
- VirtualBox
- vagrant-guest_ansible
  - `vagrant plugin install vagrant-guest_ansible`
- For Linux/MacOS: ansible

### Cloud environment

- Packer
- DigitalOcean account with API-key and sufficient credits ($0.01 should be enough to build the image)

## Procedure to start a new system

### Local environment

- Make sure the required software is installed
- If required, edit the variables in `ansible/playbook.yml` (see below)
- Put your web application in  `www/html/`
- Download the correct base box with `vagrant box add bento/centos-7.3 --provider virtualbox`
- Start the VM with `vagrant up`.
- Once the VM has booted, your application will be available with IP address  `192.168.33.10`

Other useful commands:
- `vagrant halt`: Stop VM
- `vagrant reload`: Restart VM
- `vagrant provision`: Execute the provisioning again
- `vagrant destroy`: Delete VM

### Digital Ocean

To create a snapshot:
- Acquire an API-key
  - Go in Digital Ocean to API > Generate new token
  - Execute in bash: `export DIGITALOCEAN_API_TOKEN=xxxx` where `xxxx` is your generated token
  - Optionally you can put the line above in `~/.bashrc` to persist this environment variable.
- Make sure Packer is installed
- Put your web application in `www/html/`
- Execute `packer build packer.json`
- A snapshot has been created under Images > Snapshots on DO

To start a new node
- Go to Images > Snapshots on Digital Ocean
- Choose the desired snapshot and click More > Create droplet
- Choose the desired droplet size (the $5 droplet should suffice)
- Choose the desired datacenter
- Add your public SSH key under "Add SSH Keys" in order to obtain root-access through SSH.
- Optionally, choose a different hostname.
- Click create
- After the node has booted, your application will be available with the indicated IP address.
- If you added your SSH key, you can reach your node with `ssh root@xxx.xxx.xxx.xxx`.

## Variables

These variables should be edited in `ansible/playbook.yml` in the `vars` section.

### Role: init

- `install_packages`: List of packages to be installed

### Role: httpd

- `server_name`: The Apache server name
- `server_admin`: Server admin email
- `document_root`: Apache document root. **Warning:** this script might break if you change this!

### Role: mariadb

- `mariadb_root_password`: MariaDB root password
- `mariadb_databases`: List of databases to be created
- `mariadb_users`: List of  MariaDB users to be created
  - `name`: Username
  - `password`: Password
  - `priv`: User privileges (ex.: `een_test_database.*:ALL,GRANT`)

## Explanation Ansible roles

### Role: init

Role to install and configure generic things. Starts the firewall, installs generic packages (`vim`, `wget`, ...), etc.

### Role: sepolicy

Creates a new SELinux policy. This is required since under vagrant the `/var/www` folder is mounted by VirtualBox. These synced folders do not support relabeling files, and by default have another label than the one required by Apache. Therefore, Apache can't read these files when SELinux is set to `enforcing`. This policy grants Apache read rights on files with the default VirtualBox label.

### Role: httpd

Installs and configures Apache.

- `install.yml`
  - Installs Apache
- `configure.yml`
  - Configures a virtual host pointing to the required document root.
  - Sets the global server name
  - Configures SELinux  
  - Starts Apache
- `secure.yml`
  - Configures the firewall to allow HTTP-traffic

### Role: php

Installs php and some modules, and restarts Apache.

### Role: mariadb

Installs and configures MariaDB.

- `install.yml`
  - Installs MariaDB
  - Starts MariaDB
- `configure.yml`
  - Configures the firewall to allox traffic on port 3306
  - Creates the required databases
  - Creates the required users
- `secure.yml`
  - Configures a root password
  - Deletes anonymous users
  - Deletes the default test database

### Role: phpMyAdmin

Installs and configures phpMyAdmin.

- Adds the `epel-release` repo. This is required to install phpMyAdmin using `yum`
- Installs phpMyAdmin
- Copies a configuration file to the Apache configuration folder in order for phpMyAdmin to be reachable through Apache.

## Explanation Vagrantfile

```ruby
config.vm.box = "centos/7"
```

Chooses the base box: CentOS 7

```ruby
config.vm.network "private_network", ip: "192.168.33.10"
```

Assign an IP address to the VM

```ruby
if Vagrant::Util::Platform.windows?
  config.vm.provision "shell", inline: "sudo yum install epel-release -y"
  config.vm.provision "shell", inline: "sudo yum install ansible -y"
  config.vm.provision "guest_ansible" do |ansible|
    ansible.playbook = "ansible/playbook.yml"
  end
else
  config.vm.provision "ansible" do |ansible|
    ansible.playbook = "ansible/playbook.yml"
  end
end
```

- In case of a Windows host machine
  - Add `epel-release` repo that contains Ansible to the VM
  - Install Ansible on the VM
  - Run the playbook locally on the VM
- In case of other OS
  - Run ansible on the host


```ruby
config.vm.synced_folder "./www/html", "/var/www/html", type: "virtualbox"
config.vm.provision "shell", inline: "sudo mount -t vboxsf -o uid=`id -u apache`,gid=`id -g apache`,dmode=775,fmode=664 var_www_html /var/www/html"
```

Mount the local folder with the webappliction to the VM. In order for Apache to operate correctly, these files should belong to "apche:apache". However, since the mounting occurs before the Ansible provisioning, this user doesn't exist yet. As a workaround, we first mount the folder without providing a specific user, and then remount it correctly after the provisioning has completed.

## Explanation packer.json

```json
{
  "type": "shell",
  "inline": ["yum install epel-release -y", "yum install ansible -y"]
}
```

Install ansible on the node.


```json
{
  "type": "ansible-local",
  "playbook_file": "./ansible/playbook.yml",
  "playbook_dir": "./ansible"
}
```

Execute the Ansible playbook.

```json
{
  "type": "file",
  "source": "www",
  "destination": "/tmp/"
},
{
  "type": "shell",
  "inline": [
    "mv /tmp/www/html /var/www/",
    "chown -R apache:apache /var/www",
    "restorecon /var/www"
  ]
}
```

Copy the web application to the `/tmp`-folder on the node. Afterwards, transfer the files to `/var/www` with the correct user, group and SELinux label.


## Sources

- https://seven.centos.org/2016/12/updated-centos-vagrant-images-available-v1611-01/
- https://github.com/skecskes/vagrant-centos7-ansible-lamp
- http://docs.ansible.com/ansible/
- https://github.com/bertvv/lampstack
- https://www.vagrantup.com/docs/
- https://github.com/mitchellh/vagrant/issues/936
- https://access.redhat.com/documentation/en-US/Red_Hat_Enterprise_Linux/6/html/Security-Enhanced_Linux/sect-Security-Enhanced_Linux-Working_with_SELinux-SELinux_Contexts_Labeling_Files.html
