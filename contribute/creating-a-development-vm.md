---
description: This describes how to create a Linux virtual machine to build NiDB
---

# Creating a Development VM

## Install VMWare Workstation Player

VMWare Player can be downloaded from [https://www.vmware.com/products/workstation-player/workstation-player-evaluation.html](https://www.vmware.com/products/workstation-player/workstation-player-evaluation.html)

## Download Linux ISO file

NiDB can be built on most RedHat compatiable Linux distributions. Download the Rocky 8 or 9 DVD ISO from [https://rockylinux.org/download/](https://rockylinux.org/download/)

## Create a VM in VMWare Workstation Player

Start VMWare Workstation Player, click **Create a New Virtual Machine**. Choose the ISO image that you downloaded. Click **Next**.

Select the Guest OS and version; in this example Linux and RHEL 9. Click **Next**.

Give your VM a meaningful name and location. Click **Next**.

Choose the disk size and format. 30GB is preferable and choose **Store virtual disk as a single file**. Click **Next**.

Click **Customize Hardware...** and change the VM hardware. If you have extra cores available on your host machine, 4 or more cores is preferable. Same with memory, if you have extra memorty available on your host machine, 8GB or more memory is preferable. When done, click **Close**.

Click **Finish**.

On the main VMWare interface, double click your new VM to start it.

## Installing Linux

Install RHEL compatible with the **Server with GUI** install option. Disable SELinux. Make sure to enable the network and assign a hostname. Also helpful to create a user and assign them root permissions.
