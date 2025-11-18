import type * as __compactRuntime from '@midnight-ntwrk/compact-runtime';

export type Ballot = { election_id: Uint8Array;
                       candidate_id: Uint8Array;
                       credential: { subject: { id: Uint8Array,
                                                first_name: Uint8Array,
                                                last_name: Uint8Array,
                                                national_identifier: Uint8Array,
                                                birth_timestamp: bigint
                                              },
                                     signature: { pk: { x: bigint, y: bigint },
                                                  R: { x: bigint, y: bigint },
                                                  s: bigint
                                                }
                                   }
                     };

export type Witnesses<T> = {
  get_ballot(context: __compactRuntime.WitnessContext<Ledger, T>): [T, Ballot];
}

export type ImpureCircuits<T> = {
  assert_is_admin(context: __compactRuntime.CircuitContext<T>): __compactRuntime.CircuitResults<T, []>;
  open_election(context: __compactRuntime.CircuitContext<T>): __compactRuntime.CircuitResults<T, []>;
  close_election(context: __compactRuntime.CircuitContext<T>): __compactRuntime.CircuitResults<T, []>;
  register_candidate(context: __compactRuntime.CircuitContext<T>,
                     candidate_id_0: Uint8Array): __compactRuntime.CircuitResults<T, []>;
  cast_vote(context: __compactRuntime.CircuitContext<T>): __compactRuntime.CircuitResults<T, []>;
}

export type PureCircuits = {
}

export type Circuits<T> = {
  assert_is_admin(context: __compactRuntime.CircuitContext<T>): __compactRuntime.CircuitResults<T, []>;
  open_election(context: __compactRuntime.CircuitContext<T>): __compactRuntime.CircuitResults<T, []>;
  close_election(context: __compactRuntime.CircuitContext<T>): __compactRuntime.CircuitResults<T, []>;
  register_candidate(context: __compactRuntime.CircuitContext<T>,
                     candidate_id_0: Uint8Array): __compactRuntime.CircuitResults<T, []>;
  cast_vote(context: __compactRuntime.CircuitContext<T>): __compactRuntime.CircuitResults<T, []>;
}

export type Ledger = {
  readonly election_admin: { bytes: Uint8Array };
  readonly trusted_issuer_public_key: { x: bigint, y: bigint };
  readonly active_election_id: Uint8Array;
  readonly election_active: bigint;
  candidate_status: {
    isEmpty(): boolean;
    size(): bigint;
    member(key_0: Uint8Array): boolean;
    lookup(key_0: Uint8Array): bigint;
    [Symbol.iterator](): Iterator<[Uint8Array, bigint]>
  };
  vote_counts: {
    isEmpty(): boolean;
    size(): bigint;
    member(key_0: Uint8Array): boolean;
    lookup(key_0: Uint8Array): bigint;
    [Symbol.iterator](): Iterator<[Uint8Array, bigint]>
  };
  voter_nullifiers: {
    isEmpty(): boolean;
    size(): bigint;
    member(key_0: Uint8Array): boolean;
    lookup(key_0: Uint8Array): bigint;
    [Symbol.iterator](): Iterator<[Uint8Array, bigint]>
  };
  readonly candidate_registry_initialized: bigint;
  readonly voter_nullifiers_initialized: bigint;
}

export type ContractReferenceLocations = any;

export declare const contractReferenceLocations : ContractReferenceLocations;

export declare class Contract<T, W extends Witnesses<T> = Witnesses<T>> {
  witnesses: W;
  circuits: Circuits<T>;
  impureCircuits: ImpureCircuits<T>;
  constructor(witnesses: W);
  initialState(context: __compactRuntime.ConstructorContext<T>,
               _trusted_issuer_public_key_0: { x: bigint, y: bigint },
               _election_id_0: Uint8Array): __compactRuntime.ConstructorResult<T>;
}

export declare function ledger(state: __compactRuntime.StateValue): Ledger;
export declare const pureCircuits: PureCircuits;
