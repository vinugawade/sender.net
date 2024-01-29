# sender.net Integration

[![CircleCI](https://dl.circleci.com/status-badge/img/gh/vinugawade/sender.net/tree/master.svg?style=svg)](https://dl.circleci.com/status-badge/redirect/gh/vinugawade/sender.net/tree/master)

The `sender.net Integration` module is used to Integrate
Drupal with <https://www.sender.net>.

## Features

 1. Add new email subscriber to <https://www.sender.net>.

## Installation

 1. Get `sender.net Integration` module & move to your module's directory.
 2. Navigate to `Administration > Extend` and install the module.

## Configurations

  1. Setup sender.net API configs in `Configuration > System > Sender.net`,
  Add `API access token` & `Base URL`.
  2. Now goto `Structure > Block Layout` & place `Sender.net Subscription`
  block in any region you want.

## Subscription

  1. Goto the page where you placed `sender.net Subscription` block.
  2. Add your email address then press `Subscribe`.
  3. Now your email is subscribed with <https://www.sender.net>.
