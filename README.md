## Allows older Minecraft client versions to connect to newer server versions.

# MultiVersion [![](https://poggit.pmmp.io/shield.dl.total/MultiVersion)](https://poggit.pmmp.io/p/MultiVersion) [![](https://poggit.pmmp.io/shield.api/MultiVersion)](https://poggit.pmmp.io/p/MultiVersion) [![Discord](https://img.shields.io/discord/902113901712379945?color=7389D8&label=discord)](https://discord.gg/NGsNaj54d6) [![Stars](https://img.shields.io/github/stars/AkmalFairuz/MultiVersion)](https://github.com/AkmalFairuz/MultiVersion/stargazers)
A Multi Version plugin for PocketMine-MP.

## Supported Versions:
| Minecraft Version | Protocol |
|-------------------|----------|
| 1.16.220          | 431      |
| 1.17.0            | 440      |
| 1.17.10           | 448      |
| 1.17.30           | 465      |
| 1.17.40           | 471      |
| 1.18.0            | 475      |
| 1.18.10           | 486      |

## Command:
| Command | Help |
| --- | ---- |
| /multiversion player `<player>` | Get player version |
| /multiversion all | Get all players version |

## Requirements:
- PHP: `>=7.4`
- PocketMine-MP: `3.26.1`

## Sources:
- PMMP protocol changes commit
- <a href="https://github.com/pmmp/BedrockProtocol">BedrockProtocol</a>

## API:
- Get player protocol:
```php
/** @var Player $player */
MultiVersion::getProtocol($player);
```
