# Verslag LAMP Stack

Auteur: Lander

## Benodigde software

### Lokale omgeving

- Vagrant
- VirtualBox
- vagrant-guest_ansible
  - `vagrant plugin install vagrant-guest_ansible`
- Onder Linux/MacOS: ansible

### Cloud omgeving

- Packer
- DigitalOcean account met API-key en voldoende credits ($0.01 volstaat normaal gezien)

## Werkwijze nieuwe machine opstarten

### Lokale omgeving

- Zorg ervoor dat de benodigde software geïnstalleerd is.
- Pas de variabelen aan naar wens in `ansible/playbook.yml` (zie onder)
- Plaats de gewenste webapplicatie onder `www/html/`
- Download de juiste base box met `vagrant box add bento/centos-7.3 --provider virtualbox`
- Voer `vagrant up` uit en laat de provisioning zijn werk doen
- Eens de machine opgestart is is de applicatie bereikbaar via het IP-adres `192.168.33.10`
- Andere nuttige commando's:
  - `vagrant halt`: Machine afsluiten
  - `vagrant reload`: Machine herstarten
  - `vagrant provision`: Provisioning opnieuw uitvoeren
  - `vagrant destroy`: Machine verwijderen

### Digital Ocean

Om een snapshot aan te maken:
- Maak een API key aan
  - Ga in Digital Ocean naar API > Generate new token
  - Voer in (Git) bash uit: `export DIGITALOCEAN_API_TOKEN=xxxx` met het gegenereerde token in plaats van `xxxx`
  - Eventueel kan je bovenstaande lijn in `~/.bashrc` zetten zodat je dit niet elke keer je bash heropstart moet doen
- Zorg ervoor dat packer geïnstalleerd is
- Plaats de gewenste webapplicatie onder `www/html/`
- Voer `packer build packer.json` uit en laat het script zijn werk doen
- Er is nu een snapshot aangemaakt onder Images > Snapshots op DO

Om een node op te starten
- Ga naar Images > Snapshots op Digital Ocean
- Klik bij het gewenste snapshot op More > Create droplet
- Kies een gewenste droplet size (de $5 droplet volstaat normaal gezien)
- Kies een datacenter (bvb Amsterdam 2)
- Voeg eventueel je publieke ssh key toe onder "Add SSH Keys". Op die manier heb je vanaf je lokale machine root-access over ssh.
- Kies eventueel een andere hostname
- Klik op create
- Nadat de node is opgestart is de webapplicatie bereikbaar op het toegewezen ip-adres
- De node is bereikbaar over ssh met `ssh root@xxx.xxx.xxx.xxx` met `xxx.xxx.xxx.xxx` het toegewezen ip-adres, op voorwaarde dat je je publieke key toevoegde aan de node.

## Gebruikte variabelen

Deze variabelen dienen een waarde te worden gegeven in `ansible/playbook.yml`
onder de `vars`-sectie.

### Role: init

- `install_packages`: Lijst van extra packages die moeten geïnstalleerd worden

### Role: httpd

- `server_name`: De servernaam die gebruikt wordt in de Apache configuratie
- `server_admin`: Emailadres van de server admin
- `document_root`: De directory waar de php-applicatie zich bevindt.

### Role: mariadb

- `mariadb_root_password`: Wachtwoord voor de MySQL root user
- `mariadb_databases`: Lijst van databases die moeten worden aangemaakt
- `mariadb_users`: Lijst van MySQL users die moeten worden aangemaakt
  - `name`: De naam van de user
  - `password`: Wachtwoord voor de gebruiker
  - `priv`: Rechten die de gebruiker krijgt (vb: `een_test_database.*:ALL,GRANT`)

## Uitleg Ansible roles

### Role: init

Deze rol wordt gebruikt om enkele algemene dingen te installeren en configureren.
In het bijzonder wordt de firewall service gestart en wordt een lijst van extra
packages (`vim`, `wget`, ...) geïnstalleerd.

### Role: sepolicy

Deze rol maakt een nieuwe SELinux policy aan. Deze is nodig omdat op de
Vagrant-omgeving de `/var/www`-folder die de webapplicatie bevat gemount wordt
door VirtualBox. Deze gesynchroniseerde mappen ondersteunen het niet om
gerelabeld te worden, en beschikken standaard om een ander label dan nodig is om
leesbaar te zijn door Apache. Hierdoor kan Apache met de default configuratie
niet aan de bestanden van de webapplicatie wanneer SELinux op enforcing staat.
Deze nieuwe policy geeft Apache leesrechten op bestanden met het label dat
VirtualBox toekent.

### Role: httpd

Installeert en configureert Apache.

- `install.yml`
  - Installeert apache.
- `configure.yml`
  - Configureert een virtual host die naar de gewenste document root wijst.
  - Stelt de globale servernaam in in de Apache config
  - Stelt SELinux in
  - Start Apache
- `secure.yml`
  - Stelt de firewall in om HTTP-verkeer door te laten.

### Role: php

Installeert php en enkele modules, en herstart Apache.

### Role: mariadb

Installeert en configureert MariaDB.

- `install.yml`
  - Installeert MariaDB
  - Start MariaDB
- `configure.yml`
  - Stelt de firewall in om poort 3306 toe te laten
  - Maakt de gevraagde databases aan
  - Maakt de gevraagde gebruikers aan
- `secure.yml`
  - Stelt een wachtwoord in voor de root gebruiker
  - Verwijdert eventuele anonieme gebruikers
  - Verwijdert de standaard testdatabase

### Role: phpmyadmin

Installeert en configureert phpmyadmin.

- Installeert de `epel-release` repo, deze is benodigd om phpmyadmin via `yum` te installeren
- Installeert phpmyadmin
- Kopieert een geschickt configuratiebestand naar de apache configuratiefolder zodat phpmyadmin te bereiken valt via de webserver.

## Uitleg Vagrantfile

```ruby
config.vm.box = "centos/7"
```

Kiest de basisbox: CentOS 7

```ruby
config.vm.network "private_network", ip: "192.168.33.10"
```

Wijst een ip-adres toe aan de machine

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

- In het geval dat Vagrant op een Windows machine wordt uitgevoerd
  - Voeg de `epel-release` repo die ansible bevat toe aan de machine
  - Installeer `ansible` op de virtuele machine
  - Voer het ansible-playbook uit op de virtuele machine
- In het geval dat Vagrant op een OS dat ansible wel ondersteunt wordt uitgevoerd
  - Voer ansible lokaal uit


```ruby
config.vm.synced_folder "./www/html", "/var/www/html", type: "virtualbox"
config.vm.provision "shell", inline: "sudo mount -t vboxsf -o uid=`id -u apache`,gid=`id -g apache`,dmode=775,fmode=664 var_www_html /var/www/html"
```

Mount de lokale map met de webapplicatie op de virtuele machine. Om Apache
correct te laten werken moeten deze bestanden aan user `apache:apache` toebehoren.
Echter, aangezien het mounten gebeurt voor de provisioning door ansible bestaat
deze user nog niet. We mounten dus eerst zonder user op te geven, en voeren dan
na de ansible-provisioning een commando uit dat de map hermount met de correcte
user en group.

## Uitleg packer.json

```json
{
  "type": "shell",
  "inline": ["yum install epel-release -y", "yum install ansible -y"]
}
```

Installeer ansible op de node.

```json
{
  "type": "ansible-local",
  "playbook_file": "./ansible/playbook.yml",
  "playbook_dir": "./ansible"
}
```

Voer het ansible playbook uit.

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

Kopieer de webapplicatie naar de `/tmp`-folder op de node. Daarna verplaatsen
we de bestanden naar `/var/www`, geven we ze de juiste user en group, en
het juiste label voor SELinux.

## Bronnen

- https://seven.centos.org/2016/12/updated-centos-vagrant-images-available-v1611-01/
- https://github.com/skecskes/vagrant-centos7-ansible-lamp
- http://docs.ansible.com/ansible/
- https://github.com/bertvv/lampstack
- https://www.vagrantup.com/docs/
- https://github.com/mitchellh/vagrant/issues/936
- https://access.redhat.com/documentation/en-US/Red_Hat_Enterprise_Linux/6/html/Security-Enhanced_Linux/sect-Security-Enhanced_Linux-Working_with_SELinux-SELinux_Contexts_Labeling_Files.html
